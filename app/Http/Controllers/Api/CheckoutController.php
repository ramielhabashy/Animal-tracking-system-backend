<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\SendsEmailNotifications;
use App\Models\SubscriptionOrder;
use App\Models\User;
use App\Services\PaymentMethodService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;

class CheckoutController extends Controller
{
    use SendsEmailNotifications;
    private function getRequestUser(Request $request): ?User
    {
        return $request->user();
    }

    public function init(Request $request): JsonResponse
    {
        $user = $this->getRequestUser($request);
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'tier_id' => 'required|exists:subscription_tiers,id',
            'billing_cycle' => 'required|in:monthly,yearly',
            'shipping_address' => 'required|array',
            'shipping_address.full_name' => 'required|string|max:255',

            'shipping_address.phone' => 'nullable|string|max:50',
            'shipping_address.street' => 'required|string|max:255',
            'shipping_address.city' => 'required|string|max:255',
            'shipping_address.state' => 'nullable|string|max:255',
            'shipping_address.zip' => 'required|string|max:20',
            'shipping_address.country' => 'required|string|max:255|in:' . implode(',', Setting::get('checkout_countries') ? json_decode(Setting::get('checkout_countries')) : ['Saudi Arabia']),
            'payment_method' => ['required', 'string', \Illuminate\Validation\Rule::in(config('payment.validation.checkout', ['stripe', 'bank_transfer']))],
        ]);

        $tier = SubscriptionTier::findOrFail($validated['tier_id']);
        $amount = $validated['billing_cycle'] === 'yearly' ? $tier->price_yearly : $tier->price_monthly;

        $order = SubscriptionOrder::create([
            'user_id' => $user->id,
            'tier_id' => $tier->id,
            'amount' => $amount,
            'currency' => 'USD',
            'billing_cycle' => $validated['billing_cycle'],
            'shipping_address' => $validated['shipping_address'],
            'payment_method' => $validated['payment_method'],
            'payment_status' => 'pending',
        ]);

        if ($validated['payment_method'] === 'stripe') {
            $stripeSettings = Setting::getStripeSettings();
            if (!$stripeSettings['enabled']) {
                return response()->json(['message' => 'Stripe payments are disabled by admin'], 400);
            }

            Stripe::setApiKey($stripeSettings['secret_key']);

            $session = StripeSession::create([
                'mode' => 'payment',
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => $tier->name . ' (' . $validated['billing_cycle'] . ')',
                        ],
                        'unit_amount' => (int)($amount * 100),
                    ],
                    'quantity' => 1,
                ]],
                'metadata' => [
                    'order_id' => $order->id,
                    'user_id' => $user->id,
                    'tier_id' => $tier->id,
                ],
                'success_url' => rtrim(env('FRONTEND_URL', config('app.url')), '/') . '/react.oasis/checkout/confirm?session_id={CHECKOUT_SESSION_ID}&order_id=' . $order->id,
                'cancel_url' => rtrim(env('FRONTEND_URL', config('app.url')), '/') . '/react.oasis/checkout?canceled=1',
            ]);

            $order->update([
                'stripe_session_id' => $session->id,
                'payment_intent_id' => $session->payment_intent,
            ]);

            return response()->json([
                'data' => [
                    'order_id' => $order->id,
                    'client_secret' => $session->client_secret ?? $session->id,
                    'session_id' => $session->id,
                    'url' => $session->url,
                ],
            ]);
        }

        return response()->json([
            'data' => [
                'order_id' => $order->id,
                'amount' => $amount,
                'currency' => 'USD',
                'billing_cycle' => $validated['billing_cycle'],
                'payment_method' => 'bank_transfer',
            ],
        ]);
    }

    public function confirm(Request $request): JsonResponse
    {
        $user = $this->getRequestUser($request);
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'order_id' => 'required|exists:subscription_orders,id',
            'stripe_session_id' => 'nullable|string',
        ]);

        $order = SubscriptionOrder::with('tier')->findOrFail($validated['order_id']);

        if ($order->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($order->payment_status === 'paid') {
            return response()->json(['message' => 'Order already confirmed', 'data' => $order->load('tier')]);
        }

        if ($order->payment_method === 'stripe' && $validated['stripe_session_id']) {
            $stripeSettings = Setting::getStripeSettings();
            Stripe::setApiKey($stripeSettings['secret_key']);

            $session = StripeSession::retrieve($validated['stripe_session_id']);
            if ($session->payment_status !== 'paid') {
                return response()->json(['message' => 'Payment not completed'], 400);
            }

            $order->update([
                'payment_status' => 'paid',
                'payment_reference' => $session->payment_intent,
            ]);
        }

        if ($order->payment_method === 'bank_transfer') {
            return response()->json([
                'message' => 'Bank transfer submitted. Awaiting admin approval.',
                'data' => $order->load('tier'),
            ]);
        }

        $this->notifyAdmins('subscription_purchased', 'New Subscription', $user->name . ' purchased ' . $order->tier->name, ['link' => '/orders', 'order_id' => $order->id]);

        $subscription = $this->createPendingSubscription($user, $order);
        $order->update(['user_subscription_id' => $subscription->id]);

        return response()->json([
            'message' => 'Payment confirmed. Activate your device to start your subscription.',
            'data' => [
                'order' => $order->load('tier'),
                'subscription' => $subscription->load('tier'),
            ],
        ]);
    }

    public function bankTransfer(Request $request): JsonResponse
    {
        $user = $this->getRequestUser($request);
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'order_id' => 'required|exists:subscription_orders,id',
            'payment_proof' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $order = SubscriptionOrder::with('tier')->findOrFail($validated['order_id']);

        if ($order->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $path = $request->file('payment_proof')->store('subscription-payments', 'public');

        $order->update([
            'payment_status' => 'pending',
            'payment_reference' => asset('storage/' . $path),
        ]);

        $this->notifyAdmins('payment_proof_submitted', 'Payment Proof Submitted', $user->name . ' submitted payment proof for ' . $order->tier->name, ['link' => '/orders', 'order_id' => $order->id]);

        $subscription = $this->createPendingSubscription($user, $order);
        $order->update(['user_subscription_id' => $subscription->id]);

        return response()->json([
            'message' => 'Bank transfer proof uploaded. Awaiting admin approval.',
            'data' => [
                'order' => $order->load('tier'),
                'subscription' => $subscription->load('tier'),
            ],
        ]);
    }

    public function myOrders(Request $request): JsonResponse
    {
        $user = $this->getRequestUser($request);
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $orders = SubscriptionOrder::with('tier')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $orders]);
    }

    public function showOrder(Request $request, SubscriptionOrder $order): JsonResponse
    {
        $user = $this->getRequestUser($request);
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if ($order->user_id !== $user->id && !$user->hasRole('Admin')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json(['data' => $order->load(['tier', 'userSubscription.tier'])]);
    }

    public function activateDevice(Request $request): JsonResponse
    {
        $user = $this->getRequestUser($request);
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'device_id' => 'required|string|exists:devices,device_id',
        ]);

        $device = Device::where('device_id', $validated['device_id'])
            ->where('owner_id', $user->id)
            ->first();

        if (!$device) {
            return response()->json(['message' => 'Device not found or not owned by you'], 404);
        }

        if ($device->user_subscription_id) {
            return response()->json(['message' => 'Device is already linked to a subscription'], 400);
        }

        $order = SubscriptionOrder::where('user_id', $user->id)
            ->where('payment_status', 'paid')
            ->whereNull('activated_at')
            ->latest()
            ->first();

        if (!$order || !$order->user_subscription_id) {
            return response()->json(['message' => 'No paid subscription order found to activate'], 400);
        }

        $subscription = UserSubscription::find($order->user_subscription_id);
        if (!$subscription) {
            return response()->json(['message' => 'Subscription not found'], 404);
        }

        $billingDays = $order->billing_cycle === 'yearly' ? 365 : 30;
        $subscription->update([
            'started_at' => now(),
            'ends_at' => now()->addDays($billingDays),
            'status' => 'active',
        ]);

        $device->update(['user_subscription_id' => $subscription->id]);

        $order->update(['activated_at' => now()]);

        $user->update(['subscription_tier_id' => $order->tier_id]);

        return response()->json([
            'message' => 'Device activated. Your subscription has started.',
            'data' => [
                'subscription' => $subscription->fresh()->load('tier'),
                'device' => $device,
            ],
        ]);
    }

    private function notifyAdmins(string $type, string $title, string $body, array $data = []): void
    {
        $admins = User::role('Admin')->get();
        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'data' => $data,
            ]);
        }
    }

    private function createPendingSubscription(User $user, SubscriptionOrder $order): UserSubscription
    {
        $status = $order->payment_method === 'stripe' ? 'pending_activation' : 'pending_payment';
        $paymentMethod = $order->payment_method === 'stripe' ? 'card' : 'bank_transfer';

        return UserSubscription::create([
            'user_id' => $user->id,
            'tier_id' => $order->tier_id,
            'status' => $status,
            'started_at' => null,
            'ends_at' => null,
            'billing_cycle' => $order->billing_cycle,
            'payment_method' => $paymentMethod,
            'payment_reference' => $order->payment_reference,
        ]);
    }

    public function adminOrders(Request $request): JsonResponse
    {
        $orders = SubscriptionOrder::with(['user', 'tier', 'userSubscription'])
            ->whereHas('user', function ($q) {
                $q->whereHas('roles', function ($r) {
                    $r->where('name', 'Owner');
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json(['data' => $orders]);
    }

    public function adminUpdateOrder(Request $request, SubscriptionOrder $order): JsonResponse
    {
        $validated = $request->validate([
            'shipping_status' => 'nullable|in:pending,shipped,delivered',
            'tracking_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $updateData = [];

        if (isset($validated['shipping_status'])) {
            $updateData['shipping_status'] = $validated['shipping_status'];
            if ($validated['shipping_status'] === 'shipped') {
                $updateData['shipped_at'] = now();
            } elseif ($validated['shipping_status'] === 'delivered') {
                $updateData['delivered_at'] = now();
            }
        }

        if (isset($validated['tracking_number'])) {
            $updateData['tracking_number'] = $validated['tracking_number'];
        }

        if (isset($validated['notes'])) {
            $updateData['notes'] = $validated['notes'];
        }

        $order->update($updateData);

        if (isset($validated['shipping_status'])) {
            $user = User::find($order->user_id);
            $tracking = $validated['tracking_number'] ?? $order->tracking_number;
            if ($user) {
                $frontendUrl = rtrim(env('FRONTEND_URL', config('app.url')), '/') . '/subscription';
                if ($validated['shipping_status'] === 'shipped') {
                    Notification::create([
                        'user_id' => $order->user_id,
                        'type' => 'order_shipped',
                        'title' => 'Order Shipped',
                        'body' => 'Your order #' . $order->id . ' has been shipped' . ($tracking ? ' (Tracking: #' . $tracking . ')' : '') . '.',
                        'data' => ['order_id' => $order->id, 'tracking' => $tracking],
                    ]);
                    $this->sendNotificationMail(
                        $user,
                        'order_shipped',
                        'Your Order #' . $order->id . ' Has Been Shipped',
                        [
                            'Your subscription order has been shipped!' . ($tracking ? ' Tracking number: ' . $tracking : ''),
                            'Plan: ' . ($order->tier?->name ?? 'Subscription'),
                            'Once delivered, activate your device to start your subscription.',
                        ],
                        $frontendUrl,
                        'View Subscription',
                    );
                } elseif ($validated['shipping_status'] === 'delivered') {
                    Notification::create([
                        'user_id' => $order->user_id,
                        'type' => 'order_delivered',
                        'title' => 'Order Delivered',
                        'body' => 'Your order #' . $order->id . ' has been delivered. Activate your device to start your subscription.',
                        'data' => ['order_id' => $order->id],
                    ]);
                    $this->sendNotificationMail(
                        $user,
                        'order_delivered',
                        'Your Order #' . $order->id . ' Has Been Delivered',
                        [
                            'Your subscription order has been delivered!',
                            'Plan: ' . ($order->tier?->name ?? 'Subscription'),
                            'Activate your device now to start your subscription.',
                        ],
                        $frontendUrl,
                        'Activate Device',
                    );
                }
            }
        }

        return response()->json([
            'message' => 'Order updated successfully',
            'data' => $order->fresh()->load(['user', 'tier']),
        ]);
    }

    public function adminApprovePayment(Request $request, SubscriptionOrder $order): JsonResponse
    {
        if ($order->payment_status !== 'pending' || $order->payment_method !== 'bank_transfer') {
            return response()->json(['message' => 'Order is not pending bank transfer approval'], 400);
        }

        $order->update(['payment_status' => 'paid']);

        $user = User::find($order->user_id);
        if ($order->user_subscription_id) {
            $subscription = UserSubscription::find($order->user_subscription_id);
            if ($subscription) {
                $subscription->update([
                    'status' => 'pending_activation',
                    'started_at' => null,
                ]);
            }
        } else {
            $subscription = $this->createPendingSubscription($user, $order);
            $order->update(['user_subscription_id' => $subscription->id]);
        }

        $tier = SubscriptionTier::find($order->tier_id);
        $tierName = $tier?->name ?? 'Subscription';

        if ($user) {
            $this->sendPaymentApprovedMail($user, $tierName, $order);
        }

        return response()->json([
            'message' => 'Payment approved. Awaiting device activation.',
            'data' => $order->fresh()->load(['user', 'tier', 'userSubscription']),
        ]);
    }

    public function adminRejectPayment(Request $request, SubscriptionOrder $order): JsonResponse
    {
        if ($order->payment_status !== 'pending') {
            return response()->json(['message' => 'Order is not pending'], 400);
        }

        $order->update(['payment_status' => 'failed']);

        $user = User::find($order->user_id);
        if ($user) {
            $this->sendPaymentRejectedMail($user, $order);
        }

        return response()->json([
            'message' => 'Payment rejected',
            'data' => $order->fresh()->load(['user', 'tier']),
        ]);
    }

    public function adminStats(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'total_orders' => SubscriptionOrder::count(),
                'pending_shipment' => SubscriptionOrder::where('shipping_status', 'pending')->where('payment_status', 'paid')->count(),
                'shipped' => SubscriptionOrder::where('shipping_status', 'shipped')->count(),
                'delivered' => SubscriptionOrder::where('shipping_status', 'delivered')->count(),
                'pending_payment' => SubscriptionOrder::where('payment_status', 'pending')->count(),
                'paid' => SubscriptionOrder::where('payment_status', 'paid')->count(),
                'revenue' => SubscriptionOrder::where('payment_status', 'paid')->sum('amount'),
            ],
        ]);
    }

    private function sendPaymentApprovedMail(User $user, string $tierName, SubscriptionOrder $order): void
    {
        try {
            $this->sendNotificationMail(
                $user,
                'subscription',
                "Payment Approved – {$tierName} Plan",
                [
                    'Your payment has been approved.',
                    "Plan: {$tierName}",
                    'Activate your device to start your subscription.',
                ],
                rtrim(env('FRONTEND_URL', config('app.url')), '/') . '/subscription',
                'View Subscription',
            );
        } catch (\Exception $e) {
            \Log::error('Failed to send payment approved email: ' . $e->getMessage());
        }
    }

    private function sendPaymentRejectedMail(User $user, SubscriptionOrder $order): void
    {
        try {
            $this->sendNotificationMail(
                $user,
                'subscription',
                'Payment Rejected',
                [
                    'Your payment for the subscription has been rejected.',
                    'Please contact support or try a different payment method.',
                ],
                rtrim(env('FRONTEND_URL', config('app.url')), '/') . '/subscription',
                'View Subscription',
            );
        } catch (\Exception $e) {
            \Log::error('Failed to send payment rejected email: ' . $e->getMessage());
        }
    }
}
