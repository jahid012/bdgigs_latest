<?php

namespace Database\Seeders;

use App\Models\ManualPaymentMethod;
use Illuminate\Database\Seeder;

class ManualPaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            [
                'name' => 'Bank Transfer',
                'account_name' => 'bdgigs Marketplace',
                'account_number' => 'BDGIGS-BANK-001',
                'instructions' => 'Send the exact package amount, then submit the transfer reference below.',
                'sort_order' => 1,
            ],
            [
                'name' => 'Mobile Wallet',
                'account_name' => 'bdgigs Payments',
                'account_number' => 'BDGIGS-WALLET-002',
                'instructions' => 'Use your wallet transaction ID as the payment reference for review.',
                'sort_order' => 2,
            ],
        ] as $method) {
            ManualPaymentMethod::updateOrCreate(
                ['name' => $method['name']],
                $method + ['active' => true],
            );
        }
    }
}
