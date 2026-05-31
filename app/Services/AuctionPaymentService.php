<?php

namespace App\Services;

use App\Models\Auction;
use App\Models\Setting;
use App\Models\User;
use App\Mail\NotificationMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AuctionPaymentService
{
    public static function processExpiredPayments(): int
    {
        $expiredAuctions = Auction::where('status', 'sold')
            ->where('payment_status', 'pending')
            ->where('payment_expires_at', '<', now())
            ->get();

        $processed = 0;
        $paymentExpiryHours = (int) Setting::get('auction_payment_expiry_hours', 24);
        $secondWinnerEnabled = Setting::getBoolean('auction_second_winner_enabled', true);

        foreach ($expiredAuctions as $auction) {
            $secondWinner = $secondWinnerEnabled ? $auction->secondWinner : null;
            $oldWinnerId = $auction->winner_id;

            if ($secondWinner) {
                $auction->update([
                    'winner_id' => $secondWinner->id,
                    'second_winner_id' => null,
                    'payment_expires_at' => now()->addHours($paymentExpiryHours),
                    'payment_status' => 'pending',
                ]);

                $oldWinner = User::find($oldWinnerId);

                \App\Models\Notification::create([
                    'user_id' => $oldWinnerId,
                    'type' => 'auction_payment_rejected',
                    'title' => 'Payment deadline passed',
                    'body' => "Your payment deadline for \"{$auction->title}\" has expired. You've lost the winning position.",
                    'data' => ['auction_id' => $auction->id],
                ]);

                if ($oldWinner) {
                    static::sendMail(
                        $oldWinner,
                        'auction_payment',
                        "Payment Deadline Passed – {$auction->title}",
                        [
                            "Your payment deadline for \"{$auction->title}\" has expired.",
                            "You've lost the winning position.",
                        ],
                        rtrim(env('FRONTEND_URL', config('app.url')), '/') . "/auctions/{$auction->id}",
                        'View Auction',
                    );
                }

                \App\Models\Notification::create([
                    'user_id' => $secondWinner->id,
                    'type' => 'auction_won',
                    'title' => "You're the new winner!",
                    'body' => "The previous winner didn't pay on time. You've been promoted to winner for \"{$auction->title}\".",
                    'data' => [
                        'auction_id' => $auction->id,
                        'link' => "/auctions/{$auction->id}",
                    ],
                ]);

                static::sendMail(
                    $secondWinner,
                    'auction_won',
                    "You're the New Winner! – {$auction->title}",
                    [
                        "The previous winner didn't pay on time.",
                        "You've been promoted to winner for \"{$auction->title}\". Please complete payment within {$paymentExpiryHours} hours.",
                    ],
                    rtrim(env('FRONTEND_URL', config('app.url')), '/') . "/auctions/{$auction->id}",
                    'Complete Payment',
                );
            } else {
                $auction->update([
                    'status' => 'ended',
                    'payment_status' => 'expired',
                ]);

                if ($oldWinnerId) {
                    $oldWinnerUser = User::find($oldWinnerId);

                    \App\Models\Notification::create([
                        'user_id' => $oldWinnerId,
                        'type' => 'auction_payment_rejected',
                        'title' => 'Payment deadline passed',
                        'body' => "Your payment deadline for \"{$auction->title}\" has expired. The auction has been ended.",
                        'data' => ['auction_id' => $auction->id],
                    ]);

                    if ($oldWinnerUser) {
                        static::sendMail(
                            $oldWinnerUser,
                            'auction_payment',
                            "Payment Deadline Passed – {$auction->title}",
                            [
                                "Your payment deadline for \"{$auction->title}\" has expired.",
                                'The auction has been ended.',
                            ],
                            rtrim(env('FRONTEND_URL', config('app.url')), '/') . "/auctions/{$auction->id}",
                            'View Auction',
                        );
                    }
                }
            }

            $processed++;
        }

        return $processed;
    }

    private static function sendMail(
        User $user,
        string $notificationType,
        string $subject,
        array $lines = [],
        ?string $actionUrl = null,
        ?string $actionText = null,
        ?string $greeting = null,
    ): void {
        if (empty($user->email)) {
            return;
        }

        try {
            if (!DB::getSchemaBuilder()->hasTable('settings')) {
                return;
            }
            $pref = DB::table('settings')
                ->where('key', 'email_notify_' . $notificationType)
                ->value('value');
            if ($pref !== null && !filter_var($pref, FILTER_VALIDATE_BOOLEAN)) {
                return;
            }
        } catch (\Throwable $e) {
            // proceed
        }

        try {
            Mail::to($user->email)->send(new NotificationMail(
                subject: $subject,
                greeting: $greeting ?? "Dear {$user->name},",
                lines: $lines,
                actionUrl: $actionUrl,
                actionText: $actionText,
                footerText: config('mail.from.name', 'Oasis Trace') . ' Team',
            ));
        } catch (\Throwable $e) {
            Log::error("Failed to send email to {$user->email}: {$e->getMessage()}");
        }
    }
}
