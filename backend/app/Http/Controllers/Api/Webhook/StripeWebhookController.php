<?php

namespace App\Http\Controllers\Api\Webhook;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;
use App\Models\User;
use App\Models\UserSubscription;

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                $webhookSecret
            );
        } catch (\Exception $e) {
            Log::error('Stripe webhook signature verification failed: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        match ($event->type) {
            'invoice.payment_succeeded' => $this->handleInvoicePaymentSucceeded($event->data->object),
            'invoice.payment_failed' => $this->handleInvoicePaymentFailed($event->data->object),
            'customer.subscription.deleted' => $this->handleSubscriptionDeleted($event->data->object),
            default => Log::info('Unhandled Stripe event: ' . $event->type),
        };

        return response()->json(['status' => 'success']);
    }

    protected function handleInvoicePaymentSucceeded($invoice): void
    {
        Log::info('Stripe invoice payment succeeded', ['invoice_id' => $invoice->id]);

        if (!isset($invoice->subscription)) {
            return;
        }

        $stripeSubscriptionId = $invoice->subscription;
        $subscription = UserSubscription::where('payment_reference', $stripeSubscriptionId)
            ->orWhere('stripe_subscription_id', $stripeSubscriptionId)
            ->first();

        if ($subscription) {
            $subscription->update([
                'status' => 'active',
                'last_payment_at' => now(),
            ]);
            Log::info('Subscription activated via webhook', ['subscription_id' => $subscription->id]);
        }
    }

    protected function handleInvoicePaymentFailed($invoice): void
    {
        Log::warning('Stripe invoice payment failed', ['invoice_id' => $invoice->id]);

        if (!isset($invoice->subscription)) {
            return;
        }

        $stripeSubscriptionId = $invoice->subscription;
        $subscription = UserSubscription::where('payment_reference', $stripeSubscriptionId)
            ->orWhere('stripe_subscription_id', $stripeSubscriptionId)
            ->first();

        if ($subscription) {
            $subscription->update([
                'status' => 'past_due',
            ]);
            Log::warning('Subscription marked as past_due', ['subscription_id' => $subscription->id]);
        }
    }

    protected function handleSubscriptionDeleted($subscription): void
    {
        $stripeSubscriptionId = $subscription->id;
        $localSubscription = UserSubscription::where('payment_reference', $stripeSubscriptionId)
            ->orWhere('stripe_subscription_id', $stripeSubscriptionId)
            ->first();

        if ($localSubscription) {
            $localSubscription->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            $user = User::find($localSubscription->user_id);
            if ($user) {
                $freeTier = \App\Models\SubscriptionTier::where('slug', 'free')->first();
                if ($freeTier) {
                    $user->update(['subscription_tier_id' => $freeTier->id]);
                }
            }

            Log::info('Subscription cancelled via webhook', ['subscription_id' => $localSubscription->id]);
        }
    }
}
