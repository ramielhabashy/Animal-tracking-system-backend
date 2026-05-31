<?php

namespace App\Http\Controllers\Api\Business;

use App\Models\SubscriptionTier;
use App\Models\UserSubscription;
use App\Models\SubscriptionOrder;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Stripe\Stripe;
use Stripe\Charge;
use Stripe\Token;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\SendsEmailNotifications;
use App\Services\FeatureGate;
use App\Services\PaymentMethodService;

class SubscriptionController extends Controller
{
    use SendsEmailNotifications;
    private function getRequestUser(Request $request): ?User
    {
        return $request->user();
    }

    private function getRequestUserId(Request $request): ?string
    {
        return $request->user() ? (string) $request->user()->id : null;
    }

    public function tiers(): JsonResponse
    {
        $tiers = SubscriptionTier::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return response()->json(['data' => $tiers]);
    }

    public function showTier(SubscriptionTier $tier): JsonResponse
    {
        return response()->json(['data' => $tier]);
    }

    public function userHistory(Request $request): JsonResponse
    {
        $user = $this->getRequestUser($request);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $subscriptions = UserSubscription::where('user_id', $user->id)
            ->with('tier')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($sub) {
                return [
                    'id' => $sub->id,
                    'tier_name' => $sub->tier?->name,
                    'tier_slug' => $sub->tier?->slug,
                    'status' => $sub->status,
                    'payment_method' => $sub->payment_method,
                    'payment_reference' => $sub->payment_reference,
                    'amount' => $sub->tier?->price_monthly,
                    'started_at' => $sub->started_at?->toISOString(),
                    'ended_at' => $sub->ends_at?->toISOString(),
                    'created_at' => $sub->created_at?->toISOString(),
                    'billing_cycle' => $sub->billing_cycle,
                ];
            });

        return response()->json(['data' => $subscriptions]);
    }

    public function userSubscription(Request $request): JsonResponse
    {
        $user = $request->user();
        $requestingUserId = $user?->id;
        $requestingUserRole = $user?->getPrimaryRoleName();
        
        $targetUserId = $request->input('user_id') ?: $requestingUserId;
        
        if ($requestingUserRole !== 'Admin' && $targetUserId != $requestingUserId) {
            return response()->json(['message' => 'Unauthorized', 'error' => 'unauthorized'], 403);
        }
        
        $user = User::find($targetUserId);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $subscription = $user->subscription()
            ->with('tier')
            ->latest()
            ->first();

        $activeSubscription = $user->subscription()
            ->with('tier')
            ->where('status', 'active')
            ->first();

        $tier = FeatureGate::getUserTier($user);

        $limits = [
            'animals' => [
                'used' => $user->getAnimalCount(),
                'max' => $tier?->max_animals ?? 0,
            ],
            'devices' => [
                'used' => $user->getDeviceCount(),
                'max' => $tier?->max_devices ?? 0,
            ],
            'users' => [
                'used' => $user->getUserCount(),
                'max' => $tier?->max_users ?? 0,
            ],
        ];

        return response()->json([
            'data' => $subscription ?? [
                'id' => null,
                'user_id' => $user->id,
                'tier_id' => $tier?->id,
                'status' => $tier ? ($activeSubscription ? 'active' : 'pending') : 'none',
            ],
            'subscription' => $subscription,
            'tier' => $tier,
            'limits' => $limits,
        ]);
    }

    public function subscribe(Request $request, SubscriptionTier $tier): JsonResponse
    {
        $user = $this->getRequestUser($request);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $existingSubscription = $user->subscription()
            ->where('status', 'active')
            ->first();

        if ($existingSubscription) {
            $currentTier = $existingSubscription->tier;
            if (!$currentTier) {
                $currentTier = SubscriptionTier::find($existingSubscription->tier_id);
            }

            if ($currentTier && $currentTier->id === $tier->id) {
                return response()->json(['message' => 'You are already subscribed to this plan'], 400);
            }

            if ($currentTier && $currentTier->sort_order < $tier->sort_order) {
                return $this->upgrade($request, $tier);
            }

            return $this->downgrade($request, $tier);
        }

        $defaultBillingDays = (int) (Setting::get('subscription_default_billing_period_days') ?? 30);

        $pendingSubscription = $user->subscription()
            ->where('status', 'pending_payment')
            ->first();

        if ($pendingSubscription) {
            $pendingSubscription->update([
                'tier_id' => $tier->id,
                'status' => 'active',
                'started_at' => now(),
                'trial_ends_at' => $tier->trial_days > 0 ? now()->addDays($tier->trial_days) : null,
                'ends_at' => now()->addDays($tier->trial_days > 0 ? $tier->trial_days : $defaultBillingDays),
            ]);
            
            $user->update(['subscription_tier_id' => $tier->id]);
            
            return response()->json([
                'message' => 'Subscription activated successfully',
                'data' => $pendingSubscription->load('tier'),
            ]);
        }

        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'tier_id' => $tier->id,
            'status' => 'active',
            'started_at' => now(),
            'trial_ends_at' => $tier->trial_days > 0 ? now()->addDays($tier->trial_days) : null,
            'ends_at' => now()->addDays($tier->trial_days > 0 ? $tier->trial_days : $defaultBillingDays),
            'billing_cycle' => 'monthly',
        ]);

        $user->update(['subscription_tier_id' => $tier->id]);

        return response()->json([
            'message' => 'Subscription created successfully',
            'data' => $subscription->load('tier'),
        ], 201);
    }

    public function upgrade(Request $request, SubscriptionTier $tier): JsonResponse
    {
        $user = $this->getRequestUser($request);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $currentSubscription = $user->subscription()
            ->where('status', 'active')
            ->first();

        if (!$currentSubscription) {
            return response()->json(['message' => 'No active subscription to upgrade'], 400);
        }

        $currentSubscription->update(['status' => 'upgraded']);

        $upgradeDefaultBillingDays = (int) (Setting::get('subscription_default_billing_period_days') ?? 30);

        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'tier_id' => $tier->id,
            'status' => 'active',
            'started_at' => now(),
            'trial_ends_at' => $tier->trial_days > 0 ? now()->addDays($tier->trial_days) : null,
            'ends_at' => now()->addDays($tier->trial_days > 0 ? $tier->trial_days : $upgradeDefaultBillingDays),
            'billing_cycle' => 'monthly',
            'payment_method' => $request->input('payment_method'),
            'payment_reference' => $request->input('payment_reference'),
        ]);

        $user->update(['subscription_tier_id' => $tier->id]);

        return response()->json([
            'message' => 'Subscription upgraded successfully',
            'data' => $subscription->load('tier'),
        ]);
    }

    public function downgrade(Request $request, SubscriptionTier $tier): JsonResponse
    {
        $user = $this->getRequestUser($request);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $currentSubscription = $user->subscription()
            ->where('status', 'active')
            ->first();

        if (!$currentSubscription) {
            return response()->json(['message' => 'No active subscription to downgrade'], 400);
        }

        $currentSubscription->update(['status' => 'downgraded']);

        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'tier_id' => $tier->id,
            'status' => 'active',
            'started_at' => now(),
            'trial_ends_at' => $tier->trial_days > 0 ? now()->addDays($tier->trial_days) : null,
            'ends_at' => now()->addDays($tier->trial_days > 0 ? $tier->trial_days : 30),
            'billing_cycle' => 'monthly',
            'payment_method' => $request->input('payment_method'),
            'payment_reference' => $request->input('payment_reference'),
        ]);

        $user->update(['subscription_tier_id' => $tier->id]);

        return response()->json([
            'message' => 'Subscription downgraded successfully',
            'data' => $subscription->load('tier'),
        ]);
    }

    public function cancel(Request $request): JsonResponse
    {
        $user = $this->getRequestUser($request);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $subscription = $user->subscription()
            ->where('status', 'active')
            ->first();

        if (!$subscription) {
            return response()->json(['message' => 'No active subscription to cancel'], 400);
        }

        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        $freeTier = SubscriptionTier::where('slug', 'free')->first();
        if ($freeTier) {
            $user->update(['subscription_tier_id' => $freeTier->id]);
        }

        $this->sendNotificationMail(
            $user,
            'subscription',
            'Subscription Cancelled',
            [
                'Your subscription has been cancelled successfully.',
                'You have been moved to the Free plan.',
                'You can reactivate anytime from your subscription page.',
            ],
            rtrim(env('FRONTEND_URL', config('app.url')), '/') . '/subscription',
            'View Subscription',
        );

        return response()->json([
            'message' => 'Subscription cancelled successfully',
            'data' => $subscription->load('tier'),
        ]);
    }

    public function adminSetTier(Request $request, User $user, SubscriptionTier $tier): JsonResponse
    {
        $admin = $this->getRequestUser($request);

        if (!$admin || !$admin->isAdmin()) {
            return response()->json(['message' => 'Unauthorized', 'error' => 'unauthorized'], 403);
        }

        $existingSubscription = $user->subscription()
            ->where('status', 'active')
            ->first();

        if ($existingSubscription) {
            $existingSubscription->update(['status' => 'cancelled', 'cancelled_at' => now()]);
        }

        $adminDefaultBillingDays = (int) (Setting::get('subscription_default_billing_period_days') ?? 30);

        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'tier_id' => $tier->id,
            'status' => 'active',
            'started_at' => now(),
            'trial_ends_at' => $tier->trial_days > 0 ? now()->addDays($tier->trial_days) : null,
            'ends_at' => now()->addDays($tier->trial_days > 0 ? $tier->trial_days : $adminDefaultBillingDays),
        ]);

        $user->update(['subscription_tier_id' => $tier->id]);

        return response()->json([
            'message' => 'Subscription tier set successfully',
            'data' => $subscription->load('tier'),
        ]);
    }

    public function adminListSubscriptions(Request $request): JsonResponse
    {
        $admin = $this->getRequestUser($request);

        if (!$admin || !$admin->isAdmin()) {
            return response()->json(['message' => 'Unauthorized', 'error' => 'unauthorized'], 403);
        }

        $users = User::with('subscriptionTier')
            ->with('subscription')
            ->withCount(['animals', 'devices', 'shepherds'])
            ->whereHas('roles', function ($q) {
                $q->where('name', 'Owner');
            })
            ->orderBy('name')
            ->get()
            ->map(function ($user) {
                $sub = $user->subscription->sortByDesc('created_at')->first();
                $effectiveTier = FeatureGate::getUserTier($user);
                $nextBillingDate = null;
                if ($sub?->ends_at) {
                    $nextBillingDate = $sub->ends_at;
                } elseif ($sub?->started_at) {
                    $period = match ($sub->billing_cycle) {
                        'yearly' => '1 year',
                        default => '1 month',
                    };
                    $nextBillingDate = $sub->started_at->copy()->add($period);
                }
                return [
                    'id' => $user->id,
                    'user_id' => $user->id,
                    'subscription_id' => $sub?->id,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->getPrimaryRoleName(),
                    ],
                    'tier_id' => $user->subscription_tier_id,
                    'tier' => $user->subscriptionTier,
                    'effective_tier' => $effectiveTier,
                    'status' => $sub?->status ?? 'none',
                    'created_at' => $sub?->created_at?->toISOString(),
                    'started_at' => $sub?->started_at?->toISOString(),
                    'ends_at' => $sub?->ends_at?->toISOString(),
                    'renewal_at' => $nextBillingDate?->toISOString(),
                    'next_billing_date' => $nextBillingDate?->toISOString(),
                    'billing_cycle' => $sub?->billing_cycle,
                    'payment_method' => $sub?->payment_method,
                    'usage' => [
                        'animals' => [
                            'used' => $user->animals_count,
                            'max' => $effectiveTier?->max_animals ?? 0,
                        ],
                        'devices' => [
                            'used' => $user->devices_count,
                            'max' => $effectiveTier?->max_devices ?? 0,
                        ],
                        'team' => [
                            'used' => $user->shepherds_count,
                            'max' => $effectiveTier?->max_users ?? 0,
                        ],
                    ],
                ];
            });

        return response()->json(['data' => $users]);
    }

    public function adminSubscriptionStats(Request $request): JsonResponse
    {
        $admin = $this->getRequestUser($request);

        if (!$admin || !$admin->isAdmin()) {
            return response()->json(['message' => 'Unauthorized', 'error' => 'unauthorized'], 403);
        }

        $totalUsers = User::whereNotNull('subscription_tier_id')->count();
        $activeSubscribers = UserSubscription::where('status', 'active')->count();
        $pendingPayments = UserSubscription::where('status', 'pending_payment')->count();
        $cancelled = UserSubscription::where('status', 'cancelled')->count();
        $pastDue = UserSubscription::where('status', 'past_due')->count();

        $tiers = SubscriptionTier::orderBy('sort_order')->get()->map(function ($tier) {
            return [
                'id' => $tier->id,
                'name' => $tier->name,
                'slug' => $tier->slug,
                'price_monthly' => $tier->price_monthly,
                'subscriber_count' => User::where('subscription_tier_id', $tier->id)->count(),
            ];
        });

        $mrr = UserSubscription::where('status', 'active')
            ->with('tier')
            ->get()
            ->sum(function ($sub) {
                return (float)($sub->tier?->price_monthly ?? 0);
            });

        $newThisMonth = UserSubscription::where('status', 'active')
            ->where('started_at', '>=', now()->startOfMonth())
            ->count();

        $churnedThisMonth = UserSubscription::where('status', 'cancelled')
            ->where('cancelled_at', '>=', now()->startOfMonth())
            ->count();

        $paymentMethods = UserSubscription::whereNotNull('payment_method')
            ->selectRaw('payment_method, COUNT(*) as count')
            ->groupBy('payment_method')
            ->pluck('count', 'payment_method');

        $revenueOverTime = UserSubscription::where('status', 'active')
            ->whereNotNull('payment_reference')
            ->where('started_at', '>=', now()->subMonths(12))
            ->with('tier')
            ->get()
            ->groupBy(function ($sub) {
                return $sub->started_at?->format('Y-m');
            })
            ->map(function ($subs, $month) {
                return [
                    'month' => $month,
                    'revenue' => $subs->sum(fn($s) => (float)($s->tier?->price_monthly ?? 0)),
                    'count' => $subs->count(),
                ];
            })
            ->values();

        $growthOverTime = UserSubscription::whereIn('status', ['active', 'cancelled', 'upgraded', 'downgraded'])
            ->where('created_at', '>=', now()->subMonths(12))
            ->get()
            ->groupBy(function ($sub) {
                return $sub->created_at?->format('Y-m');
            })
            ->map(function ($subs, $month) {
                return [
                    'month' => $month,
                    'new' => $subs->where('status', 'active')->count(),
                    'cancelled' => $subs->where('status', 'cancelled')->count(),
                ];
            })
            ->values();

        $recentSubscriptions = UserSubscription::with(['user', 'tier'])
            ->latest()
            ->take(20)
            ->get()
            ->map(function ($sub) {
                return [
                    'id' => $sub->id,
                    'user_id' => $sub->user_id,
                    'user_name' => $sub->user?->name ?? 'Unknown',
                    'user_email' => $sub->user?->email ?? '',
                    'tier_name' => $sub->tier?->name ?? 'Unknown',
                    'status' => $sub->status,
                    'started_at' => $sub->started_at?->toISOString(),
                    'ends_at' => $sub->ends_at?->toISOString(),
                    'payment_method' => $sub->payment_method,
                    'billing_cycle' => $sub->billing_cycle,
                ];
            });

        return response()->json([
            'data' => [
                'total_users' => $totalUsers,
                'active_subscribers' => $activeSubscribers,
                'pending_payments' => $pendingPayments,
                'cancelled' => $cancelled,
                'past_due' => $pastDue,
                'mrr' => round($mrr, 2),
                'new_this_month' => $newThisMonth,
                'churned_this_month' => $churnedThisMonth,
                'tier_distribution' => $tiers,
                'payment_methods' => $paymentMethods,
                'revenue_over_time' => $revenueOverTime,
                'growth_over_time' => $growthOverTime,
                'recent_subscriptions' => $recentSubscriptions,
            ],
        ]);
    }

    public function createTier(Request $request): JsonResponse
    {
        $admin = $this->getRequestUser($request);

        if (!$admin || !$admin->isAdmin()) {
            return response()->json(['message' => 'Unauthorized', 'error' => 'unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:subscription_tiers,slug',
            'description' => 'nullable|string',
            'price_monthly' => 'required|numeric|min:0',
            'price_yearly' => 'required|numeric|min:0',
            'trial_days' => 'nullable|integer|min:0',
            'max_animals' => 'required|integer|min:0',
            'max_devices' => 'required|integer|min:0',
            'max_users' => 'required|integer|min:0',
            'has_geofencing' => 'nullable|boolean',
            'has_auctions' => 'nullable|boolean',
            'has_advanced_reports' => 'nullable|boolean',
            'has_api_access' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
            'is_yearly_only' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['is_yearly_only'] = $request->boolean('is_yearly_only');

        $tier = SubscriptionTier::create($validated);

        return response()->json([
            'message' => 'Tier created successfully',
            'data' => $tier,
        ], 201);
    }

    public function updateTier(Request $request, SubscriptionTier $tier): JsonResponse
    {
        $admin = $this->getRequestUser($request);

        if (!$admin || !$admin->isAdmin()) {
            return response()->json(['message' => 'Unauthorized', 'error' => 'unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:255|unique:subscription_tiers,slug,' . $tier->id,
            'description' => 'nullable|string',
            'price_monthly' => 'sometimes|numeric|min:0',
            'price_yearly' => 'sometimes|numeric|min:0',
            'trial_days' => 'nullable|integer|min:0',
            'max_animals' => 'sometimes|integer|min:0',
            'max_devices' => 'sometimes|integer|min:0',
            'max_users' => 'sometimes|integer|min:0',
            'has_geofencing' => 'nullable|boolean',
            'has_auctions' => 'nullable|boolean',
            'has_advanced_reports' => 'nullable|boolean',
            'has_api_access' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
            'is_yearly_only' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['is_yearly_only'] = $request->boolean('is_yearly_only');

        $tier->update($validated);

        return response()->json([
            'message' => 'Tier updated successfully',
            'data' => $tier,
        ]);
    }

    public function deleteTier(SubscriptionTier $tier): JsonResponse
    {
        $admin = $this->getRequestUser(request());

        if (!$admin || !$admin->isAdmin()) {
            return response()->json(['message' => 'Unauthorized', 'error' => 'unauthorized'], 403);
        }

        if ($tier->slug === 'free') {
            return response()->json(['message' => 'Cannot delete the free tier'], 400);
        }

        $tier->update(['is_active' => false]);

        return response()->json(['message' => 'Tier deactivated successfully']);
    }

    public function processPayment(Request $request): JsonResponse
    {
        return response()->json(['message' => 'This payment method is deprecated. Use Stripe Checkout instead.', 'error' => 'deprecated'], 410);

        $user = $this->getRequestUser($request);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $validated = $request->validate([
            'tier_id' => 'required|exists:subscription_tiers,id',
            'card_number' => 'required|string',
            'expiry' => 'required|string',
            'cvc' => 'required|string',
        ]);

        $tier = SubscriptionTier::find($validated['tier_id']);

        if ($tier->price_monthly === '0.00') {
            $this->activateSubscription($user, $tier);
            return response()->json(['message' => 'Free subscription activated']);
        }

        try {
            $stripeSettings = \App\Models\Setting::getStripeSettings();
            Stripe::setApiKey($stripeSettings['secret_key']);

            $expiry = $validated['expiry'];
            $expMonth = null;
            $expYear = null;
            if (strpos($expiry, '/') !== false) {
                [$expMonth, $expYear] = explode('/', $expiry);
            } else {
                $expMonth = substr($expiry, 0, 2);
                $expYear = substr($expiry, 2, 2);
            }

            $token = Token::create([
                'card' => [
                    'number' => $validated['card_number'],
                    'exp_month' => (int)$expMonth,
                    'exp_year' => (int)$expYear,
                    'cvc' => $validated['cvc'],
                ],
            ]);

            $charge = Charge::create([
                'amount' => (float)$tier->price_monthly * 100,
                'currency' => 'usd',
                'source' => $token->id,
                'description' => "Subscription to {$tier->name} plan",
                'metadata' => [
                    'user_id' => $user->id,
                    'tier_id' => $tier->id,
                ],
            ]);

            if ($charge->status === 'succeeded') {
                $existingActive = $user->subscription()
                    ->where('status', 'active')
                    ->first();

                if ($existingActive) {
                    $existingTier = $existingActive->tier;
                    $isUpgrade = $existingTier && $existingTier->sort_order < $tier->sort_order;
                    $existingActive->update(['status' => $isUpgrade ? 'upgraded' : 'downgraded']);
                }

                $this->activateSubscription($user, $tier);

                $processDefaultBillingDays = (int) (Setting::get('subscription_default_billing_period_days') ?? 30);
                $subscription = UserSubscription::create([
                    'user_id' => $user->id,
                    'tier_id' => $tier->id,
                    'status' => 'active',
                    'started_at' => now(),
                    'ends_at' => now()->addDays($processDefaultBillingDays),
                    'billing_cycle' => 'monthly',
                    'payment_method' => 'card',
                    'payment_reference' => $charge->id,
                ]);

                return response()->json([
                    'message' => 'Payment successful',
                    'payment_id' => $charge->id,
                    'data' => $subscription->load('tier'),
                ]);
            }

            return response()->json(['message' => 'Payment failed'], 400);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Payment error: ' . $e->getMessage()], 400);
        }
    }

    public function bankTransfer(Request $request): JsonResponse
    {
        $user = $this->getRequestUser($request);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $validated = $request->validate([
            'tier_id' => 'required|exists:subscription_tiers,id',
            'payment_proof' => 'required|file|mimes:pdf|max:10240',
        ]);

        $tier = SubscriptionTier::find($validated['tier_id']);
        $path = $request->file('payment_proof')->store('subscription-payments', 'public');

        $bankDefaultBillingDays = (int) (Setting::get('subscription_default_billing_period_days') ?? 30);

        $existingActive = $user->subscription()
            ->where('status', 'active')
            ->first();

        if ($existingActive) {
            $existingTier = $existingActive->tier;
            $isUpgrade = $existingTier && $existingTier->sort_order < $tier->sort_order;
            $existingActive->update(['status' => $isUpgrade ? 'upgraded' : 'downgraded']);
        }

        UserSubscription::create([
            'user_id' => $user->id,
            'tier_id' => $tier->id,
            'status' => 'pending_payment',
            'started_at' => now(),
            'ends_at' => now()->addDays($bankDefaultBillingDays),
            'billing_cycle' => 'monthly',
            'payment_method' => 'bank_transfer',
            'payment_reference' => asset('storage/' . $path),
        ]);

        $this->sendNotificationMail(
            $user,
            'subscription',
            'Payment Proof Received',
            [
                "Your payment proof for the {$tier->name} plan has been received.",
                'It will be reviewed by an administrator and you will be notified once approved.',
            ],
            rtrim(env('FRONTEND_URL', config('app.url')), '/') . '/subscription',
            'View Subscription',
        );

        return response()->json([
            'message' => 'Bank transfer proof uploaded. You will be notified once approved.',
        ]);
    }

    protected function activateSubscription(User $user, SubscriptionTier $tier): void
    {
        $user->update(['subscription_tier_id' => $tier->id]);
    }

    public function adminApprovePayment(Request $request, UserSubscription $subscription): JsonResponse
    {
        $admin = $this->getRequestUser($request);

        if (!$admin || !$admin->isAdmin()) {
            return response()->json(['message' => 'Unauthorized', 'error' => 'unauthorized'], 403);
        }

        if ($subscription->status !== 'pending_payment') {
            return response()->json(['message' => 'This subscription is not pending approval'], 400);
        }

        $user = User::find($subscription->user_id);
        $tier = SubscriptionTier::find($subscription->tier_id);

        if (!$user || !$tier) {
            return response()->json(['message' => 'User or tier not found'], 404);
        }

        $approveDefaultBillingDays = (int) (Setting::get('subscription_default_billing_period_days') ?? 30);
        $subscription->update([
            'status' => 'active',
            'started_at' => now(),
            'ends_at' => now()->addDays($approveDefaultBillingDays),
        ]);

        $user->update(['subscription_tier_id' => $tier->id]);

        if ($user) {
            $this->sendNotificationMail(
                $user,
                'subscription',
                'Payment Approved – ' . ($tier->name ?? 'Subscription') . ' Plan Activated',
                [
                    'Your payment has been approved and your subscription is now active.',
                    "Plan: {$tier->name}",
                    "Valid until: {$subscription->ends_at?->format('M d, Y')}",
                ],
                rtrim(env('FRONTEND_URL', config('app.url')), '/') . '/subscription',
                'View Subscription',
            );
        }

        return response()->json([
            'message' => 'Payment approved and subscription activated',
            'data' => $subscription->load(['user', 'tier']),
        ]);
    }

    public function adminRejectPayment(Request $request, UserSubscription $subscription): JsonResponse
    {
        $admin = $this->getRequestUser($request);

        if (!$admin || !$admin->isAdmin()) {
            return response()->json(['message' => 'Unauthorized', 'error' => 'unauthorized'], 403);
        }

        if ($subscription->status !== 'pending_payment') {
            return response()->json(['message' => 'This subscription is not pending approval'], 400);
        }

        $subscription->update(['status' => 'rejected']);

        $user = User::find($subscription->user_id);
        if ($user) {
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
        }

        return response()->json([
            'message' => 'Payment rejected',
            'data' => $subscription,
        ]);
    }

    public function adminListPendingPayments(Request $request): JsonResponse
    {
        $admin = $this->getRequestUser($request);

        if (!$admin || !$admin->isAdmin()) {
            return response()->json(['message' => 'Unauthorized', 'error' => 'unauthorized'], 403);
        }

        $pendingSubscriptions = UserSubscription::with(['user', 'tier'])
            ->where('status', 'pending_payment')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $pendingSubscriptions]);
    }

    public function adminPauseSubscription(Request $request, User $user): JsonResponse
    {
        $admin = $this->getRequestUser($request);

        if (!$admin || !$admin->isAdmin()) {
            return response()->json(['message' => 'Unauthorized', 'error' => 'unauthorized'], 403);
        }

        $subscription = $user->subscription()
            ->where('status', 'active')
            ->first();

        if (!$subscription) {
            return response()->json(['message' => 'No active subscription found'], 404);
        }

        $subscription->update([
            'status' => 'paused',
            'paused_at' => now(),
        ]);

        $this->sendNotificationMail(
            $user,
            'subscription',
            'Subscription Paused',
            [
                'Your subscription has been paused by an administrator.',
                'You can reactivate it anytime by contacting support.',
            ],
            rtrim(env('FRONTEND_URL', config('app.url')), '/') . '/subscription',
            'View Subscription',
        );

        return response()->json([
            'message' => 'Subscription paused successfully',
            'data' => $subscription->fresh()->load('tier'),
        ]);
    }

    public function adminReactivateSubscription(Request $request, User $user): JsonResponse
    {
        $admin = $this->getRequestUser($request);

        if (!$admin || !$admin->isAdmin()) {
            return response()->json(['message' => 'Unauthorized', 'error' => 'unauthorized'], 403);
        }

        $subscription = $user->subscription()
            ->whereIn('status', ['paused', 'cancelled'])
            ->latest()
            ->first();

        if (!$subscription) {
            return response()->json(['message' => 'No paused or cancelled subscription found'], 404);
        }

        $subscription->update([
            'status' => 'active',
            'paused_at' => null,
            'cancelled_at' => null,
        ]);

        $user->update(['subscription_tier_id' => $subscription->tier_id]);

        $tierName = $subscription->tier?->name ?? 'Subscription';
        $this->sendNotificationMail(
            $user,
            'subscription',
            "Subscription Reactivated – {$tierName}",
            [
                "Your {$tierName} plan has been reactivated by an administrator.",
                "You now have full access to your plan features.",
            ],
            rtrim(env('FRONTEND_URL', config('app.url')), '/') . '/subscription',
            'View Subscription',
        );

        return response()->json([
            'message' => 'Subscription reactivated successfully',
            'data' => $subscription->fresh()->load('tier'),
        ]);
    }

    public function adminCancelSubscription(Request $request, User $user): JsonResponse
    {
        $admin = $this->getRequestUser($request);

        if (!$admin || !$admin->isAdmin()) {
            return response()->json(['message' => 'Unauthorized', 'error' => 'unauthorized'], 403);
        }

        $subscription = $user->subscription()
            ->whereIn('status', ['active', 'paused'])
            ->latest()
            ->first();

        if (!$subscription) {
            return response()->json(['message' => 'No active or paused subscription found'], 404);
        }

        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        $freeTier = SubscriptionTier::where('slug', 'free')->first();
        $user->update(['subscription_tier_id' => $freeTier?->id]);

        $tierName = $subscription->tier?->name ?? 'Subscription';
        $this->sendNotificationMail(
            $user,
            'subscription',
            "Subscription Cancelled – {$tierName}",
            [
                "Your {$tierName} subscription has been cancelled by an administrator.",
                'You have been moved to the Free plan.',
            ],
            rtrim(env('FRONTEND_URL', config('app.url')), '/') . '/subscription',
            'View Subscription',
        );

        return response()->json([
            'message' => 'Subscription cancelled successfully',
            'data' => $subscription->fresh()->load('tier'),
        ]);
    }

    public function adminUpdateSubscription(Request $request, UserSubscription $subscription): JsonResponse
    {
        $admin = $this->getRequestUser($request);

        if (!$admin || !$admin->isAdmin()) {
            return response()->json(['message' => 'Unauthorized', 'error' => 'unauthorized'], 403);
        }

        $validated = $request->validate([
            'payment_method' => 'nullable|string|max:50',
            'payment_reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'billing_cycle' => 'nullable|in:monthly,yearly',
        ]);

        $subscription->update($validated);

        return response()->json([
            'message' => 'Subscription updated successfully',
            'data' => $subscription->fresh()->load(['user', 'tier']),
        ]);
    }

    public function renew(Request $request): JsonResponse
    {
        $user = $this->getRequestUser($request);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $subscription = $user->subscription()
            ->where('status', 'active')
            ->first();

        if (!$subscription) {
            return response()->json(['message' => 'No active subscription to renew'], 400);
        }

        $tier = $subscription->tier;

        if (!$tier || (float)$tier->price($subscription->billing_cycle ?? 'monthly') <= 0) {
            $period = $subscription->billing_cycle === 'yearly' ? '1 year' : '1 month';
            $subscription->update([
                'ends_at' => now()->add($period),
            ]);

            $tierName = $tier?->name ?? 'Subscription';
            $this->sendNotificationMail(
                $user,
                'subscription',
                "Subscription Renewed – {$tierName}",
                [
                    "Your {$tierName} plan has been renewed successfully.",
                    "New expiry date: {$subscription->ends_at?->format('M d, Y')}",
                ],
                rtrim(env('FRONTEND_URL', config('app.url')), '/') . '/subscription',
                'View Subscription',
            );

            return response()->json([
                'message' => 'Subscription renewed successfully',
                'data' => $subscription->fresh()->load('tier'),
            ]);
        }

        $amount = (float)$tier->price($subscription->billing_cycle ?? 'monthly');
        $currency = $tier->currency ?? 'usd';
        $paymentMethod = $subscription->payment_method ?? config('payment.default', 'card');

        if (!PaymentMethodService::isValid($paymentMethod, 'subscription')) {
            return response()->json(['message' => 'Unsupported payment method: ' . $paymentMethod], 400);
        }

        if (in_array($paymentMethod, ['card', 'stripe'], true)) {
            $stripeSettings = Setting::getStripeSettings();

            if (empty($stripeSettings['secret_key']) || !$stripeSettings['enabled']) {
                return response()->json(['message' => 'Stripe payment is not configured'], 500);
            }

            Stripe::setApiKey($stripeSettings['secret_key']);

            $order = SubscriptionOrder::create([
                'user_id' => $user->id,
                'tier_id' => $tier->id,
                'user_subscription_id' => $subscription->id,
                'amount' => $amount,
                'currency' => $currency,
                'billing_cycle' => $subscription->billing_cycle ?? 'monthly',
                'payment_method' => 'card',
                'payment_status' => 'pending',
                'notes' => 'Subscription renewal',
            ]);

            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => strtolower($currency),
                        'product_data' => ['name' => "{$tier->name} Renewal"],
                        'unit_amount' => (int)($amount * 100),
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => rtrim(env('FRONTEND_URL', config('app.url')), '/') . '/react.oasis/checkout/confirm/' . $order->id,
                'cancel_url' => rtrim(env('FRONTEND_URL', config('app.url')), '/') . '/react.oasis/subscription',
                'metadata' => [
                    'order_id' => (string)$order->id,
                    'user_id' => (string)$user->id,
                    'type' => 'renewal',
                ],
            ]);

            $order->update([
                'stripe_session_id' => $session->id,
            ]);

            return response()->json([
                'message' => 'Redirecting to payment',
                'data' => [
                    'checkout_url' => $session->url,
                    'session_id' => $session->id,
                    'order_id' => $order->id,
                ],
            ]);
        }

        if ($paymentMethod === 'bank_transfer') {
            SubscriptionOrder::create([
                'user_id' => $user->id,
                'tier_id' => $tier->id,
                'user_subscription_id' => $subscription->id,
                'amount' => $amount,
                'currency' => $currency,
                'billing_cycle' => $subscription->billing_cycle ?? 'monthly',
                'payment_method' => 'bank_transfer',
                'payment_status' => 'pending',
                'notes' => 'Subscription renewal - pending bank transfer',
            ]);

            $subscription->update([
                'status' => 'pending_payment',
            ]);

            $tierName = $tier->name ?? 'Subscription';
            $this->sendNotificationMail(
                $user,
                'subscription',
                "Renewal Pending – {$tierName}",
                [
                    "Your {$tierName} renewal request has been submitted.",
                    "The subscription will renew once the payment is confirmed.",
                ],
                rtrim(env('FRONTEND_URL', config('app.url')), '/') . '/react.oasis/subscription',
                'View Subscription',
            );

            return response()->json([
                'message' => 'Renewal submitted. Awaiting payment confirmation.',
                'data' => $subscription->fresh()->load('tier'),
            ]);
        }

        return response()->json(['message' => 'Unsupported payment method: ' . $paymentMethod], 400);
    }

    public function reactivate(Request $request): JsonResponse
    {
        $user = $this->getRequestUser($request);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $subscription = $user->subscription()
            ->whereIn('status', ['paused', 'cancelled'])
            ->latest()
            ->first();

        if (!$subscription) {
            return response()->json(['message' => 'No paused or cancelled subscription found'], 404);
        }

        $subscription->update([
            'status' => 'active',
            'paused_at' => null,
            'cancelled_at' => null,
        ]);

        $user->update(['subscription_tier_id' => $subscription->tier_id]);

        return response()->json([
            'message' => 'Subscription reactivated successfully',
            'data' => $subscription->fresh()->load('tier'),
        ]);
    }

    public function adminChangeBillingCycle(Request $request, UserSubscription $subscription): JsonResponse
    {
        $admin = $this->getRequestUser($request);

        if (!$admin || !$admin->isAdmin()) {
            return response()->json(['message' => 'Unauthorized', 'error' => 'unauthorized'], 403);
        }

        $validated = $request->validate([
            'billing_cycle' => 'required|in:monthly,yearly',
        ]);

        $subscription->update([
            'billing_cycle' => $validated['billing_cycle'],
        ]);

        return response()->json([
            'message' => 'Billing cycle updated successfully',
            'data' => $subscription->fresh()->load('tier'),
        ]);
    }
}