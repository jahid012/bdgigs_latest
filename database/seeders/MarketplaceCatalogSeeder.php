<?php

namespace Database\Seeders;

use App\Models\Gig;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MarketplaceCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Jahid',
                'avatar' => 'https://images.pexels.com/photos/3785077/pexels-photo-3785077.jpeg?auto=compress&cs=tinysrgb&w=240',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'profile_type' => 'seller',
                'country' => 'Bangladesh',
                'verification_status' => 'verified',
            ],
        );

        $user->sellerProfile()->updateOrCreate([], [
            'professional_title' => 'Laravel and React marketplace builder',
            'about' => 'I build focused marketplace experiences with Laravel APIs and React dashboards.',
            'languages' => [
                ['id' => 'english', 'language' => 'English', 'proficiency' => 'Fluent'],
                ['id' => 'bengali', 'language' => 'Bengali', 'proficiency' => 'Native/Bilingual'],
            ],
            'skills' => ['Laravel', 'React', 'Marketplace dashboards'],
            'portfolio_projects' => [],
        ]);

        foreach ($this->sellerServices() as $service) {
            Gig::updateOrCreate(
                ['slug' => $service['slug']],
                [
                    'seller_id' => $user->id,
                    'seller_name' => $user->name,
                    'seller_avatar' => $user->avatar,
                    'seller_level' => 'Level 2',
                    'title' => $service['title'],
                    'image' => $service['image'],
                    'category_id' => Str::slug($service['category']),
                    'category_label' => $service['category'],
                    'price_cents' => $service['price'] * 100,
                    'rating' => $service['rating'],
                    'reviews' => 25,
                    'delivery_days' => $service['delivery'],
                    'seller_details' => ['level-2', 'english', 'online'],
                    'service_options' => [Str::slug($service['category']), 'design', 'performance'],
                    'instant' => true,
                    'search_text' => strtolower($service['title'].' '.$service['category']),
                    'tag' => $service['tag'],
                    'orders_label' => $service['orders'],
                    'conversion_label' => $service['conversion'],
                    'status' => $service['status'],
                    'status_class' => $service['statusClass'],
                    'packages' => $this->packagesFor($service['price']),
                    'gallery_images' => [$service['image'], '/assets/img/gig_images/1.png', '/assets/img/gig_images/2.png'],
                ],
            );
        }

        foreach ($this->listingGigs() as $gigData) {
            $seller = User::updateOrCreate(
                ['email' => Str::slug($gigData['seller']).'@bdgigs.test'],
                [
                    'name' => $gigData['seller'],
                    'avatar' => 'https://images.pexels.com/photos/220453/pexels-photo-220453.jpeg?auto=compress&cs=tinysrgb&w=240',
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'profile_type' => 'seller',
                    'country' => 'Bangladesh',
                    'verification_status' => 'verified',
                ],
            );

            Gig::updateOrCreate(
                ['slug' => $gigData['slug']],
                [
                    'seller_id' => $seller->id,
                    'seller_name' => $gigData['seller'],
                    'seller_avatar' => $seller->avatar,
                    'seller_level' => $gigData['price'] > 90 ? 'Top Rated' : 'Level 2',
                    'title' => $gigData['title'],
                    'image' => $gigData['image'],
                    'category_id' => Str::slug($gigData['category']),
                    'category_label' => $gigData['category'],
                    'price_cents' => $gigData['price'] * 100,
                    'rating' => $gigData['rating'],
                    'reviews' => $gigData['reviews'],
                    'delivery_days' => $gigData['delivery'],
                    'seller_details' => ['level-2', 'english', 'online'],
                    'service_options' => $gigData['options'],
                    'pro' => $gigData['price'] > 90,
                    'instant' => $gigData['delivery'] <= 3,
                    'consultation' => true,
                    'featured' => $gigData['featured'] ?? false,
                    'search_text' => strtolower($gigData['title'].' '.$gigData['category'].' '.implode(' ', $gigData['options'])),
                    'tag' => $gigData['category'],
                    'packages' => $this->packagesFor($gigData['price']),
                    'gallery_images' => [$gigData['image'], '/assets/img/gig_images/20.png', '/assets/img/gig_images/21.png'],
                ],
            );
        }
    }

    private function sellerServices(): array
    {
        return [
            ['slug' => 'modern-website-landing-page-design', 'title' => 'Modern Website Landing Page Design', 'category' => 'UI/UX Design', 'price' => 75, 'image' => '/assets/img/gig_images/6.png', 'tag' => 'Best Seller', 'delivery' => 3, 'rating' => 4.9, 'orders' => '42 active', 'conversion' => '18% conversion', 'status' => 'Live', 'statusClass' => 'status-completed'],
            ['slug' => 'complete-saas-dashboard-ui-design', 'title' => 'Complete SaaS Dashboard UI Design', 'category' => 'Product Design', 'price' => 140, 'image' => '/assets/img/gig_images/8.png', 'tag' => 'Trending', 'delivery' => 5, 'rating' => 5.0, 'orders' => '26 active', 'conversion' => '14% conversion', 'status' => 'Live', 'statusClass' => 'status-completed'],
            ['slug' => 'premium-brand-identity-starter-pack', 'title' => 'Premium Brand Identity Starter Pack', 'category' => 'Brand Design', 'price' => 95, 'image' => '/assets/img/gig_images/11.png', 'tag' => 'New Leads', 'delivery' => 4, 'rating' => 4.8, 'orders' => '18 active', 'conversion' => '11% conversion', 'status' => 'Optimize', 'statusClass' => 'status-delivered'],
            ['slug' => 'ai-landing-page-conversion-audit', 'title' => 'AI Landing Page Conversion Audit', 'category' => 'Growth Design', 'price' => 110, 'image' => '/assets/img/gig_images/13.png', 'tag' => 'High Intent', 'delivery' => 2, 'rating' => 4.9, 'orders' => '15 active', 'conversion' => '16% conversion', 'status' => 'Live', 'statusClass' => 'status-completed'],
        ];
    }

    private function listingGigs(): array
    {
        return [
            ['slug' => 'wordpress-redesign', 'title' => 'I will design and redesign wordpress website, blog or ecommerce store', 'seller' => 'Mark', 'image' => '/assets/img/gig_images/3.png', 'category' => 'Website Customization', 'price' => 80, 'rating' => 4.9, 'reviews' => 266, 'delivery' => 3, 'options' => ['wordpress', 'design', 'performance', 'paid-video']],
            ['slug' => 'shopify-store', 'title' => 'I will build shopify website, shopify store design redesign for your ecommerce', 'seller' => 'Armina Yasmin', 'image' => '/assets/img/gig_images/12.png', 'category' => 'Web Application Development', 'price' => 95, 'rating' => 4.9, 'reviews' => 40, 'delivery' => 4, 'options' => ['shopify', 'design', 'performance', 'subscriptions']],
            ['slug' => 'wix-redesign', 'title' => 'I will build wix website, design wix or redesign wix website for your business', 'seller' => 'Samor Malakar', 'image' => '/assets/img/gig_images/16.png', 'category' => 'Custom Websites Development', 'price' => 80, 'rating' => 5.0, 'reviews' => 174, 'delivery' => 5, 'options' => ['wix', 'design', 'localization', 'paid-video']],
            ['slug' => 'full-stack-website', 'title' => 'I will develop a full stack website using react, laravel, php and modern css', 'seller' => 'Nusrat Dev', 'image' => '/assets/img/gig_images/1.png', 'category' => 'Web Application Development', 'price' => 150, 'rating' => 5.0, 'reviews' => 82, 'delivery' => 7, 'options' => ['php', 'react', 'tailwind', 'custom-websites', 'security']],
            ['slug' => 'codecanyon-install', 'title' => 'I will install and configure any PHP script codecanyon laravel website', 'seller' => 'Biswajit N', 'image' => '/assets/img/gig_images/7.png', 'category' => 'Script Development', 'price' => 10, 'rating' => 4.9, 'reviews' => 621, 'delivery' => 1, 'options' => ['php', 'java', 'bootstrap', 'security', 'paid-video'], 'featured' => true],
            ['slug' => 'nextjs-codecanyon', 'title' => 'I will codecanyon script installation service and customization', 'seller' => 'Hossain', 'image' => '/assets/img/gig_images/15.png', 'category' => 'Web Application Development', 'price' => 30, 'rating' => 4.9, 'reviews' => 122, 'delivery' => 3, 'options' => ['php', 'react', 'tailwind', 'custom-websites']],
        ];
    }

    private function packagesFor(int $basePrice): array
    {
        return [
            ['id' => 'basic', 'label' => 'Basic', 'name' => 'Basic', 'description' => 'Starter package', 'delivery' => '3 Days Delivery', 'revisions' => 'Unlimited', 'price' => (string) $basePrice],
            ['id' => 'standard', 'label' => 'Standard', 'name' => 'Standard', 'description' => 'Standard package', 'delivery' => '5 Days Delivery', 'revisions' => 'Unlimited', 'price' => (string) ($basePrice * 2)],
            ['id' => 'premium', 'label' => 'Premium', 'name' => 'Premium', 'description' => 'Premium package', 'delivery' => '10 Days Delivery', 'revisions' => 'Unlimited', 'price' => (string) ($basePrice * 4)],
        ];
    }
}
