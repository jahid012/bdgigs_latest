<?php

namespace Database\Seeders;

use App\Models\Gig;
use App\Models\User;
use App\Services\GigMediaSyncService;
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

            if (! $seller->avatar) {
                $seller->forceFill([
                    'avatar' => $sellerData['avatar'],
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

                $gig = Gig::updateOrCreate(
                    ['slug' => $draft->slug],
                    collect($draft->attributesToArray())
                        ->except(['id', 'created_at', 'updated_at'])
                        ->all(),
                );
                app(GigMediaSyncService::class)->sync($gig, [], $gig->gallery_images ?? []);

                $gigNumber++;
            }
        }
    }

    private function demoSellers(): array
    {
        return [
            [
                'email' => 'test@example.com',
                'name' => 'Jahid',
                'country' => 'Bangladesh',
                'focus' => 'Laravel and React',
                'avatar' => $this->profileImagePath(1),
            ],
            [
                'email' => 'demo-seller-02@bdgigs.test',
                'name' => 'skcoder',
                'country' => 'Bangladesh',
                'focus' => 'Web applications',
                'avatar' => $this->profileImagePath(2),
            ],
            [
                'email' => 'demo-seller-03@bdgigs.test',
                'name' => 'Armina Yasmin',
                'country' => 'Bangladesh',
                'focus' => 'Shopify builds',
                'avatar' => $this->profileImagePath(3),
            ],
            [
                'email' => 'demo-seller-04@bdgigs.test',
                'name' => 'Nusrat Dev',
                'country' => 'Bangladesh',
                'focus' => 'Product dashboards',
                'avatar' => $this->profileImagePath(4),
            ],
            [
                'email' => 'demo-seller-05@bdgigs.test',
                'name' => 'Mark Studio',
                'country' => 'United States',
                'focus' => 'WordPress sites',
                'avatar' => $this->profileImagePath(5),
            ],
            [
                'email' => 'demo-seller-06@bdgigs.test',
                'name' => 'Wiznic Solution',
                'country' => 'Pakistan',
                'focus' => 'AI websites',
                'avatar' => $this->profileImagePath(6),
            ],
            [
                'email' => 'demo-seller-07@bdgigs.test',
                'name' => 'Samor Malakar',
                'country' => 'Bangladesh',
                'focus' => 'Wix customization',
                'avatar' => $this->profileImagePath(7),
            ],
            [
                'email' => 'demo-seller-08@bdgigs.test',
                'name' => 'Biswajit N',
                'country' => 'Bangladesh',
                'focus' => 'Script installs',
                'avatar' => $this->profileImagePath(8),
            ],
            [
                'email' => 'demo-seller-09@bdgigs.test',
                'name' => 'Ahmad Dev',
                'country' => 'Pakistan',
                'focus' => 'Full stack systems',
                'avatar' => $this->profileImagePath(9),
            ],
            [
                'email' => 'demo-seller-10@bdgigs.test',
                'name' => 'Cloudpeak Labs',
                'country' => 'United States',
                'focus' => 'Landing pages',
                'avatar' => $this->profileImagePath(10),
            ],
        ];
    }

    private function profileImagePath(int $imageNumber): string
    {
        return "/assets/img/profile_images/{$imageNumber}.png";
    }
}
