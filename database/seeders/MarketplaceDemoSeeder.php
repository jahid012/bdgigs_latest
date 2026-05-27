<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class MarketplaceDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            MarketplaceContentSeeder::class,
            MarketplaceCatalogSeeder::class,
            ManualPaymentMethodSeeder::class,
            MarketplaceActivitySeeder::class,
        ]);
    }
}
