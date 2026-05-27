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
                    'last_seen_at' => $sellerData['online']
                        ? now()->subSeconds(30)
                        : now()->subMinutes(35),
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
                'portfolio_projects' => [$this->portfolioProject($sellerData)],
                'work_experience' => $this->workExperience($sellerData),
            ]);

            foreach (range(1, 4) as $slot) {
                $reviewCount = 18 + (($gigNumber * 13) % 180);
                $rating = round(4.6 + (($gigNumber % 4) * 0.1), 1);
                $draft = Gig::factory()
                    ->withSeller($seller)
                    ->make([
                        'slug' => sprintf('demo-gig-%03d', $gigNumber),
                        'featured' => false,
                        'rating' => $rating,
                        'reviews' => $reviewCount,
                        'metadata' => [
                            'catalogSlot' => $slot,
                            'source' => 'factory-seeder',
                            'reviewSample' => $this->reviewSample($sellerData, $rating, $slot),
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
                'online' => true,
            ],
            [
                'email' => 'demo-seller-02@bdgigs.test',
                'name' => 'skcoder',
                'country' => 'Bangladesh',
                'focus' => 'Web applications',
                'avatar' => $this->profileImagePath(2),
                'online' => true,
            ],
            [
                'email' => 'demo-seller-03@bdgigs.test',
                'name' => 'Armina Yasmin',
                'country' => 'Bangladesh',
                'focus' => 'Shopify builds',
                'avatar' => $this->profileImagePath(3),
                'online' => false,
            ],
            [
                'email' => 'demo-seller-04@bdgigs.test',
                'name' => 'Nusrat Dev',
                'country' => 'Bangladesh',
                'focus' => 'Product dashboards',
                'avatar' => $this->profileImagePath(4),
                'online' => false,
            ],
            [
                'email' => 'demo-seller-05@bdgigs.test',
                'name' => 'Mark Studio',
                'country' => 'United States',
                'focus' => 'WordPress sites',
                'avatar' => $this->profileImagePath(5),
                'online' => true,
            ],
            [
                'email' => 'demo-seller-06@bdgigs.test',
                'name' => 'Wiznic Solution',
                'country' => 'Pakistan',
                'focus' => 'AI websites',
                'avatar' => $this->profileImagePath(6),
                'online' => false,
            ],
            [
                'email' => 'demo-seller-07@bdgigs.test',
                'name' => 'Samor Malakar',
                'country' => 'Bangladesh',
                'focus' => 'Wix customization',
                'avatar' => $this->profileImagePath(7),
                'online' => true,
            ],
            [
                'email' => 'demo-seller-08@bdgigs.test',
                'name' => 'Biswajit N',
                'country' => 'Bangladesh',
                'focus' => 'Script installs',
                'avatar' => $this->profileImagePath(8),
                'online' => false,
            ],
            [
                'email' => 'demo-seller-09@bdgigs.test',
                'name' => 'Ahmad Dev',
                'country' => 'Pakistan',
                'focus' => 'Full stack systems',
                'avatar' => $this->profileImagePath(9),
                'online' => true,
            ],
            [
                'email' => 'demo-seller-10@bdgigs.test',
                'name' => 'Cloudpeak Labs',
                'country' => 'United States',
                'focus' => 'Landing pages',
                'avatar' => $this->profileImagePath(10),
                'online' => false,
            ],
        ];
    }

    private function profileImagePath(int $imageNumber): string
    {
        return "/assets/img/profile_images/{$imageNumber}.png";
    }

    private function portfolioProject(array $sellerData): array
    {
        return [
            'id' => 'demo-'.str($sellerData['focus'])->slug()->toString(),
            'name' => $sellerData['focus'].' launch project',
            'industry' => str_contains(strtolower($sellerData['focus']), 'wordpress') ? 'Business' : 'Programming & Tech',
            'expertise' => $sellerData['focus'],
            'duration' => '1-3 months',
            'cost' => '1200',
            'startedMonth' => 'May',
            'startedYear' => '2026',
            'madeOnFiverr' => true,
            'image' => '/assets/img/gig_images/1.png',
            'mediaCount' => 3,
            'linkedCatalog' => $sellerData['focus'],
            'description' => 'A recent project showing planning, implementation, communication, and delivery quality for '.$sellerData['focus'].'.',
        ];
    }

    private function reviewSample(array $sellerData, float $rating, int $slot): array
    {
        return [
            'name' => ['Maya Chen', 'Rahim Uddin', 'Oliver Smith', 'Ayesha Khan'][$slot - 1],
            'badge' => 'Verified order',
            'country' => ['United States', 'Bangladesh', 'United Kingdom', 'Pakistan'][$slot - 1],
            'rating' => $rating,
            'date' => now()->subDays(6 + $slot)->format('M d, Y'),
            'text' => "{$sellerData['name']} communicated clearly and delivered a polished {$sellerData['focus']} result on time.",
            'price' => '$'.number_format(120 + ($slot * 35)),
            'duration' => (3 + $slot).' days',
        ];
    }

    private function workExperience(array $sellerData): array
    {
        return [
            'title' => $sellerData['focus'].' Specialist',
            'employmentType' => 'Freelance',
            'company' => $sellerData['name'].' Studio',
            'startDate' => '2024-01-01',
            'endDate' => '',
            'duration' => '2 yrs',
            'description' => 'Plans, builds, and delivers marketplace-ready '.$sellerData['focus'].' projects for clients.',
            'skills' => [$sellerData['focus'], 'Client communication'],
        ];
    }
}
