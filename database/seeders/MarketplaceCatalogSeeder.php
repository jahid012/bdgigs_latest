<?php

namespace Database\Seeders;

use App\Models\Gig;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MarketplaceCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $gigNumber = 1;

        foreach ($this->demoSellers() as $sellerData) {
            $seller = User::updateOrCreate(
                ['email' => $sellerData['email']],
                [
                    'name' => $sellerData['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'profile_type' => 'seller',
                    'country' => $sellerData['country'],
                    'verification_status' => 'verified',
                ],
            );

            if (! $seller->username) {
                $seller->forceFill([
                    'username' => User::uniqueUsername($seller->name),
                ])->save();
            }

            $seller->sellerProfile()->updateOrCreate([], [
                'professional_title' => 'Marketplace seller for '.$sellerData['focus'],
                'about' => 'I deliver clear '.strtolower($sellerData['focus']).' packages through bdgigs.',
                'languages' => [
                    ['id' => 'english', 'language' => 'English', 'proficiency' => 'Fluent'],
                ],
                'skills' => [$sellerData['focus'], 'Client communication', 'Package delivery'],
                'portfolio_projects' => [],
            ]);

            foreach (range(1, 4) as $slot) {
                $draft = Gig::factory()
                    ->withSeller($seller)
                    ->make([
                        'slug' => sprintf('demo-gig-%03d', $gigNumber),
                        'featured' => false,
                        'metadata' => [
                            'catalogSlot' => $slot,
                            'source' => 'factory-seeder',
                        ],
                    ]);

                Gig::updateOrCreate(
                    ['slug' => $draft->slug],
                    collect($draft->attributesToArray())
                        ->except(['id', 'created_at', 'updated_at'])
                        ->all(),
                );

                $gigNumber++;
            }
        }
    }

    private function demoSellers(): array
    {
        return [
            ['email' => 'test@example.com', 'name' => 'Jahid', 'country' => 'Bangladesh', 'focus' => 'Laravel and React'],
            ['email' => 'demo-seller-02@bdgigs.test', 'name' => 'skcoder', 'country' => 'Bangladesh', 'focus' => 'Web applications'],
            ['email' => 'demo-seller-03@bdgigs.test', 'name' => 'Armina Yasmin', 'country' => 'Bangladesh', 'focus' => 'Shopify builds'],
            ['email' => 'demo-seller-04@bdgigs.test', 'name' => 'Nusrat Dev', 'country' => 'Bangladesh', 'focus' => 'Product dashboards'],
            ['email' => 'demo-seller-05@bdgigs.test', 'name' => 'Mark Studio', 'country' => 'United States', 'focus' => 'WordPress sites'],
            ['email' => 'demo-seller-06@bdgigs.test', 'name' => 'Wiznic Solution', 'country' => 'Pakistan', 'focus' => 'AI websites'],
            ['email' => 'demo-seller-07@bdgigs.test', 'name' => 'Samor Malakar', 'country' => 'Bangladesh', 'focus' => 'Wix customization'],
            ['email' => 'demo-seller-08@bdgigs.test', 'name' => 'Biswajit N', 'country' => 'Bangladesh', 'focus' => 'Script installs'],
            ['email' => 'demo-seller-09@bdgigs.test', 'name' => 'Ahmad Dev', 'country' => 'Pakistan', 'focus' => 'Full stack systems'],
            ['email' => 'demo-seller-10@bdgigs.test', 'name' => 'Cloudpeak Labs', 'country' => 'United States', 'focus' => 'Landing pages'],
        ];
    }
}
