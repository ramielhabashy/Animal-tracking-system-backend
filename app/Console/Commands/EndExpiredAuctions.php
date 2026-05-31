<?php

namespace App\Console\Commands;

use App\Models\Auction;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Console\Command;

class EndExpiredAuctions extends Command
{
    protected $signature = 'auctions:end-expired';
    protected $description = 'End all expired auctions that have not been manually ended';

    public function handle(): int
    {
        $expiredAuctions = Auction::where('status', 'active')
            ->where('ends_at', '<=', now())
            ->get();

        $count = 0;
        $paymentExpiryHours = (int) Setting::get('auction_payment_expiry_hours', 24);
        foreach ($expiredAuctions as $auction) {
            $highestBid = $auction->highestBid();

            if ($highestBid) {
                $sold = $auction->reserve_price 
                    ? $highestBid->amount >= $auction->reserve_price 
                    : true;

                if ($sold) {
                    $secondHighest = $auction->secondHighestBid();
                    
                    $auction->update([
                        'status' => 'sold',
                        'winner_id' => $highestBid->user_id,
                        'second_winner_id' => $secondHighest?->user_id,
                        'ended_at' => now(),
                        'payment_expires_at' => now()->addHours($paymentExpiryHours),
                        'payment_status' => 'pending',
                    ]);

                    \App\Models\Notification::create([
                        'user_id' => $highestBid->user_id,
                        'type' => 'auction_won',
                        'title' => 'You won the auction!',
                        'body' => "Congratulations! You won \"{$auction->title}\" for {$highestBid->amount} SAR. Complete payment within {$paymentExpiryHours} hours.",
                        'data' => [
                            'auction_id' => $auction->id,
                            'link' => "/auctions/{$auction->id}",
                        ],
                    ]);
                    
                    $this->info("Auction #{$auction->id} sold to user #{$highestBid->user_id}");
                } else {
                    $auction->update([
                        'status' => 'ended',
                        'ended_at' => now(),
                    ]);

                    \App\Models\Notification::create([
                        'user_id' => $auction->owner_id,
                        'type' => 'auction_ended',
                        'title' => 'Auction ended - reserve not met',
                        'body' => "Your auction \"{$auction->title}\" ended without meeting the reserve price.",
                        'data' => [
                            'auction_id' => $auction->id,
                            'link' => "/auctions/{$auction->id}",
                        ],
                    ]);
                    
                    $this->info("Auction #{$auction->id} ended (reserve not met)");
                }
            } else {
                $auction->update([
                    'status' => 'ended',
                    'ended_at' => now(),
                ]);

                \App\Models\Notification::create([
                    'user_id' => $auction->owner_id,
                    'type' => 'auction_ended',
                    'title' => 'Auction ended - no bids',
                    'body' => "Your auction \"{$auction->title}\" ended with no bids placed.",
                    'data' => [
                        'auction_id' => $auction->id,
                        'link' => "/auctions/{$auction->id}",
                    ],
                ]);
                
                $this->info("Auction #{$auction->id} ended (no bids)");
            }

            $count++;
        }

        $this->info("Processed {$count} expired auctions");
        return Command::SUCCESS;
    }
}
