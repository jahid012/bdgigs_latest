<?php

namespace Database\Seeders;

use App\Models\Conversation;
use App\Models\Gig;
use App\Models\Order;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MarketplaceDemoSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Jahid',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'profile_type' => 'seller',
                'country' => 'Bangladesh',
                'verification_status' => 'verified',
            ]
        );

        $sellerServices = [
            [
                'slug' => 'modern-website-landing-page-design',
                'title' => 'Modern Website Landing Page Design',
                'category' => 'UI/UX Design',
                'price' => 75,
                'image' => '/assets/img/gig_images/6.png',
                'tag' => 'Best Seller',
                'delivery' => 3,
                'rating' => 4.9,
                'orders' => '42 active',
                'conversion' => '18% conversion',
                'status' => 'Live',
                'statusClass' => 'status-completed',
            ],
            [
                'slug' => 'complete-saas-dashboard-ui-design',
                'title' => 'Complete SaaS Dashboard UI Design',
                'category' => 'Product Design',
                'price' => 140,
                'image' => '/assets/img/gig_images/8.png',
                'tag' => 'Trending',
                'delivery' => 5,
                'rating' => 5.0,
                'orders' => '26 active',
                'conversion' => '14% conversion',
                'status' => 'Live',
                'statusClass' => 'status-completed',
            ],
            [
                'slug' => 'premium-brand-identity-starter-pack',
                'title' => 'Premium Brand Identity Starter Pack',
                'category' => 'Brand Design',
                'price' => 95,
                'image' => '/assets/img/gig_images/11.png',
                'tag' => 'New Leads',
                'delivery' => 4,
                'rating' => 4.8,
                'orders' => '18 active',
                'conversion' => '11% conversion',
                'status' => 'Optimize',
                'statusClass' => 'status-delivered',
            ],
            [
                'slug' => 'ai-landing-page-conversion-audit',
                'title' => 'AI Landing Page Conversion Audit',
                'category' => 'Growth Design',
                'price' => 110,
                'image' => '/assets/img/gig_images/13.png',
                'tag' => 'High Intent',
                'delivery' => 2,
                'rating' => 4.9,
                'orders' => '15 active',
                'conversion' => '16% conversion',
                'status' => 'Live',
                'statusClass' => 'status-completed',
            ],
        ];

        foreach ($sellerServices as $service) {
            Gig::updateOrCreate(
                ['slug' => $service['slug']],
                [
                    'seller_id' => $user->id,
                    'seller_name' => $user->name,
                    'seller_avatar' => 'https://images.pexels.com/photos/3785077/pexels-photo-3785077.jpeg?auto=compress&cs=tinysrgb&w=80',
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
                ]
            );
        }

        $listingGigs = [
            ['slug' => 'wordpress-redesign', 'title' => 'I will design and redesign wordpress website, blog or ecommerce store', 'seller' => 'Mark', 'image' => '/assets/img/gig_images/3.png', 'category' => 'Website Customization', 'price' => 80, 'rating' => 4.9, 'reviews' => 266, 'delivery' => 3, 'options' => ['wordpress', 'design', 'performance', 'paid-video']],
            ['slug' => 'shopify-store', 'title' => 'I will build shopify website, shopify store design redesign for your ecommerce', 'seller' => 'Armina Yasmin', 'image' => '/assets/img/gig_images/12.png', 'category' => 'Web Application Development', 'price' => 95, 'rating' => 4.9, 'reviews' => 40, 'delivery' => 4, 'options' => ['shopify', 'design', 'performance', 'subscriptions']],
            ['slug' => 'wix-redesign', 'title' => 'I will build wix website, design wix or redesign wix website for your business', 'seller' => 'Samor Malakar', 'image' => '/assets/img/gig_images/16.png', 'category' => 'Custom Websites Development', 'price' => 80, 'rating' => 5.0, 'reviews' => 174, 'delivery' => 5, 'options' => ['wix', 'design', 'localization', 'paid-video']],
            ['slug' => 'full-stack-website', 'title' => 'I will develop a full stack website using react, laravel, php and modern css', 'seller' => 'Nusrat Dev', 'image' => '/assets/img/gig_images/1.png', 'category' => 'Web Application Development', 'price' => 150, 'rating' => 5.0, 'reviews' => 82, 'delivery' => 7, 'options' => ['php', 'react', 'tailwind', 'custom-websites', 'security']],
            ['slug' => 'codecanyon-install', 'title' => 'I will install and configure any PHP script codecanyon laravel website', 'seller' => 'Biswajit N', 'image' => '/assets/img/gig_images/7.png', 'category' => 'Script Development', 'price' => 10, 'rating' => 4.9, 'reviews' => 621, 'delivery' => 1, 'options' => ['php', 'java', 'bootstrap', 'security', 'paid-video'], 'featured' => true],
            ['slug' => 'nextjs-codecanyon', 'title' => 'I will codecanyon script installation service and customization', 'seller' => 'Hossain', 'image' => '/assets/img/gig_images/15.png', 'category' => 'Web Application Development', 'price' => 30, 'rating' => 4.9, 'reviews' => 122, 'delivery' => 3, 'options' => ['php', 'react', 'tailwind', 'custom-websites']],
        ];

        foreach ($listingGigs as $gigData) {
            $seller = User::updateOrCreate(
                ['email' => Str::slug($gigData['seller']).'@bdgigs.test'],
                [
                    'name' => $gigData['seller'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'profile_type' => 'seller',
                    'country' => 'Bangladesh',
                    'verification_status' => 'verified',
                ]
            );

            Gig::updateOrCreate(
                ['slug' => $gigData['slug']],
                [
                    'seller_id' => $seller->id,
                    'seller_name' => $gigData['seller'],
                    'seller_avatar' => 'https://images.pexels.com/photos/220453/pexels-photo-220453.jpeg?auto=compress&cs=tinysrgb&w=80',
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
                ]
            );
        }

        $user->savedServices()->syncWithoutDetaching(
            Gig::whereIn('slug', ['wordpress-redesign', 'shopify-store', 'full-stack-website'])->pluck('id')->all()
        );

        $this->seedOrders($user);
        $this->seedConversations($user);
        $this->seedNotifications($user);
    }

    private function packagesFor(int $basePrice): array
    {
        return [
            ['id' => 'basic', 'label' => 'Basic', 'name' => 'Basic', 'description' => 'Starter package', 'delivery' => '3 Days Delivery', 'revisions' => 'Unlimited', 'price' => (string) $basePrice],
            ['id' => 'standard', 'label' => 'Standard', 'name' => 'Standard', 'description' => 'Standard package', 'delivery' => '5 Days Delivery', 'revisions' => 'Unlimited', 'price' => (string) ($basePrice * 2)],
            ['id' => 'premium', 'label' => 'Premium', 'name' => 'Premium', 'description' => 'Premium package', 'delivery' => '10 Days Delivery', 'revisions' => 'Unlimited', 'price' => (string) ($basePrice * 4)],
        ];
    }

    private function seedOrders(User $user): void
    {
        $orders = [
            ['code' => 'SH-1048', 'role' => 'buyer', 'service' => 'Landing page design', 'seller' => 'Nadia R.', 'status' => 'In Progress', 'class' => 'status-progress', 'due' => '2026-05-12', 'price' => 320],
            ['code' => 'SH-1042', 'role' => 'buyer', 'service' => 'SEO content audit', 'seller' => 'Ayesha K.', 'status' => 'Delivered', 'class' => 'status-delivered', 'due' => '2026-05-08', 'price' => 140],
            ['code' => 'SH-2094', 'role' => 'seller', 'service' => 'Premium marketplace landing page', 'buyer' => 'CloudPeak Labs', 'status' => 'In Progress', 'class' => 'status-progress', 'due' => '2026-05-11', 'price' => 480],
            ['code' => 'SH-2089', 'role' => 'seller', 'service' => 'Mobile app UI kit', 'buyer' => 'BrightCart', 'status' => 'Delivered', 'class' => 'status-delivered', 'due' => '2026-05-08', 'price' => 360],
        ];

        foreach ($orders as $order) {
            $counterpartyName = $order['role'] === 'buyer'
                ? $order['seller']
                : $order['buyer'];
            $counterparty = User::updateOrCreate(
                ['email' => Str::slug($counterpartyName).'@bdgigs.test'],
                [
                    'name' => $counterpartyName,
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'profile_type' => $order['role'] === 'buyer' ? 'seller' : 'buyer',
                    'country' => $order['role'] === 'buyer' ? 'Bangladesh' : 'United States',
                    'verification_status' => 'verified',
                ]
            );

            Order::updateOrCreate(
                ['code' => $order['code']],
                [
                    'buyer_id' => $order['role'] === 'buyer' ? $user->id : $counterparty->id,
                    'seller_id' => $order['role'] === 'seller' ? $user->id : $counterparty->id,
                    'service' => $order['service'],
                    'buyer_name' => $order['buyer'] ?? $user->name,
                    'seller_name' => $order['seller'] ?? $user->name,
                    'status' => $order['status'],
                    'status_class' => $order['class'],
                    'due_date' => $order['due'],
                    'price_cents' => $order['price'] * 100,
                    'earnings_cents' => $order['price'] * 100,
                ]
            );
        }
    }

    private function seedConversations(User $user): void
    {
        $counterpart = User::updateOrCreate(
            ['email' => 'cloudpeak@bdgigs.test'],
            [
                'name' => 'CloudPeak Labs',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'profile_type' => 'buyer',
                'country' => 'United States',
                'verification_status' => 'verified',
            ]
        );

        $conversation = Conversation::updateOrCreate(
            ['public_id' => 'seller-thread-1'],
            [
                'buyer_id' => $counterpart->id,
                'seller_id' => $user->id,
                'subject' => 'Premium marketplace landing page',
                'buyer_name' => 'CloudPeak Labs',
                'seller_name' => $user->name,
                'created_by_id' => $counterpart->id,
                'context_type' => 'order',
                'context_id' => 'SH-2094',
                'status' => 'In Progress',
                'status_class' => 'status-progress',
                'priority' => 'Needs reply',
                'seller_unread_count' => 3,
                'last_message_at' => now()->subMinutes(6),
            ]
        );

        $conversation->messages()->delete();
        $conversation->messages()->createMany([
            ['sender_id' => $counterpart->id, 'recipient_id' => $user->id, 'sender_name' => 'CloudPeak Labs', 'body' => 'Can we review the pricing block before the next milestone?', 'sent_at' => now()->subMinutes(8)],
            ['sender_id' => $user->id, 'recipient_id' => $counterpart->id, 'sender_name' => $user->name, 'body' => 'Yes. I can send an updated comparison layout in the next delivery.', 'sent_at' => now()->subMinutes(6)],
        ]);

        $conversation->participants()->updateOrCreate(
            ['user_id' => $counterpart->id],
            ['context_role' => 'buying', 'unread_count' => 0]
        );
        $conversation->participants()->updateOrCreate(
            ['user_id' => $user->id],
            ['context_role' => 'selling', 'unread_count' => 3]
        );
    }

    private function seedNotifications(User $user): void
    {
        foreach ([
            ['type' => 'Needs reply', 'title' => 'New buyer note', 'detail' => 'CloudPeak Labs added feedback to the pricing block.'],
            ['type' => 'Growth', 'title' => 'Gig performance rising', 'detail' => 'Your landing page gig gained 18% more clicks this week.'],
        ] as $notification) {
            UserNotification::firstOrCreate(
                ['user_id' => $user->id, 'title' => $notification['title']],
                $notification + ['user_id' => $user->id]
            );
        }
    }
}
