<?php

namespace App\Console\Commands;

use App\Models\UserSubscription;
use App\Models\Notification;
use App\Models\Setting;
use Illuminate\Console\Command;

class SendRenewalReminders extends Command
{
    protected $signature = 'subscriptions:send-renewal-reminders';
    protected $description = 'Send renewal reminders for subscriptions expiring soon';

    public function handle()
    {
        $reminderDays = (int) (Setting::get('subscription_renewal_reminder_days') ?? 7);

        $expiringSubs = UserSubscription::where('status', 'active')
            ->whereNotNull('ends_at')
            ->whereBetween('ends_at', [now(), now()->addDays($reminderDays)])
            ->get();

        foreach ($expiringSubs as $sub) {
            $daysRemaining = now()->diffInDays($sub->ends_at, false);

            // Check if reminder already sent in last 24h
            $existingReminder = Notification::where('user_id', $sub->user_id)
                ->where('type', 'subscription_renewal_reminder')
                ->where('created_at', '>=', now()->subDay())
                ->exists();

            if (!$existingReminder) {
                Notification::create([
                    'user_id' => $sub->user_id,
                    'type' => 'subscription_renewal_reminder',
                    'title' => 'Subscription Renewal Reminder',
                    'body' => "Your subscription will expire in {$daysRemaining} day(s). Renew now to avoid interruption.",
                    'data' => ['link' => '/subscription/select'],
                ]);

                $this->info("Renewal reminder sent to user #{$sub->user_id} ({$daysRemaining} days remaining)");
            }
        }

        $this->info('Renewal reminder check completed.');
        return Command::SUCCESS;
    }
}
