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

class MarketplaceActivitySeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'test@example.com')->firstOrFail();

        $user->savedServices()->syncWithoutDetaching(
            Gig::whereIn('slug', ['wordpress-redesign', 'shopify-store', 'full-stack-website'])
                ->pluck('id')
                ->all(),
        );
        $user->buyerProfile()->updateOrCreate([], [
            'overview' => 'I hire product and marketplace specialists for focused launches.',
            'working_days' => ['start' => 'Sunday', 'end' => 'Thursday'],
            'working_hours' => ['start' => '09:00', 'end' => '18:00'],
            'timezone' => 'Asia/Dhaka',
            'languages' => ['English', 'Bengali'],
        ]);
        $user->billingProfile()->updateOrCreate([], [
            'full_name' => $user->name,
            'country' => $user->country,
        ]);

        $this->seedOrders($user);
        $this->seedConversations($user);
        $this->seedNotifications($user);
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
            $counterpartyName = $order['role'] === 'buyer' ? $order['seller'] : $order['buyer'];
            $counterparty = User::updateOrCreate(
                ['email' => Str::slug($counterpartyName).'@bdgigs.test'],
                [
                    'name' => $counterpartyName,
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'profile_type' => $order['role'] === 'buyer' ? 'seller' : 'buyer',
                    'country' => $order['role'] === 'buyer' ? 'Bangladesh' : 'United States',
                    'verification_status' => 'verified',
                ],
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
                ],
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
            ],
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
            ],
        );

        $conversation->messages()->delete();
        $conversation->messages()->createMany([
            ['sender_id' => $counterpart->id, 'recipient_id' => $user->id, 'sender_name' => 'CloudPeak Labs', 'body' => 'Can we review the pricing block before the next milestone?', 'sent_at' => now()->subMinutes(8)],
            ['sender_id' => $user->id, 'recipient_id' => $counterpart->id, 'sender_name' => $user->name, 'body' => 'Yes. I can send an updated comparison layout in the next delivery.', 'sent_at' => now()->subMinutes(6)],
        ]);
        $conversation->participants()->updateOrCreate(
            ['user_id' => $counterpart->id],
            ['context_role' => 'buying', 'unread_count' => 0],
        );
        $conversation->participants()->updateOrCreate(
            ['user_id' => $user->id],
            ['context_role' => 'selling', 'unread_count' => 3],
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
                $notification + ['user_id' => $user->id],
            );
        }
    }
}
