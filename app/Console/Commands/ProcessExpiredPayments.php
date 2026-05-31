<?php

namespace App\Console\Commands;

use App\Services\AuctionPaymentService;
use Illuminate\Console\Command;

class ProcessExpiredPayments extends Command
{
    protected $signature = 'auctions:process-expired-payments';
    protected $description = 'Process auctions where payment deadline has expired — promote second winner or end auction';

    public function handle(): int
    {
        $processed = AuctionPaymentService::processExpiredPayments();

        $this->info("Processed {$processed} expired payments");

        return Command::SUCCESS;
    }
}
