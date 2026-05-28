<?php

namespace App\Console\Commands;

use App\Services\CustomOfferService;
use Illuminate\Console\Command;

class ExpireCustomOffers extends Command
{
    protected $signature = 'custom-offers:expire';

    protected $description = 'Expire pending or accepted custom offers past their expiry timestamp.';

    public function handle(CustomOfferService $offers): int
    {
        $count = $offers->expireDueOffers();
        $this->info("Custom offers expired: {$count}");

        return self::SUCCESS;
    }
}
