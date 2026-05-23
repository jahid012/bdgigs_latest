<?php

namespace Database\Seeders;

use App\Models\Conversation;
use App\Models\Dispute;
use App\Models\Gig;
use App\Models\Order;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Database\Seeder;

class MarketplaceActivitySeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'test@example.com')->firstOrFail();

        $user->savedServices()->syncWithoutDetaching(
            Gig::query()
                ->where('seller_id', '!=', $user->id)
                ->take(3)
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
        $this->seedDisputes();
        $this->seedNotifications($user);
    }

    private function seedOrders(User $user): void
    {
        Order::whereIn('code', ['SH-1048', 'SH-1042', 'SH-2094', 'SH-2089'])->delete();

        $allMarketplaceGigs = Gig::query()
            ->with('seller')
            ->where('seller_id', '!=', $user->id)
            ->get();
        $marketplaceGigs = $allMarketplaceGigs
            ->groupBy('seller_id')
            ->map(fn ($sellerGigs) => $sellerGigs->first())
            ->values();
        $marketplaceGigs = $marketplaceGigs
            ->concat($allMarketplaceGigs->diff($marketplaceGigs))
            ->take(10)
            ->values();
        $userGigs = $user->gigs()->get()->values();
        $sellerBuyers = User::query()
            ->where('id', '!=', $user->id)
            ->whereHas('gigs')
            ->take(10)
            ->get()
            ->values();

        foreach ($marketplaceGigs as $index => $gig) {
            $this->upsertOrder(
                sprintf('BO-%03d', $index + 1),
                Order::factory()
                    ->between($user, $gig->seller, $gig)
                    ->make($this->statusState($index)),
            );
        }

        foreach (range(0, 9) as $index) {
            $gig = $userGigs[$index % max(1, $userGigs->count())] ?? null;

            if (! $gig) {
                continue;
            }

            $buyer = $sellerBuyers[$index % max(1, $sellerBuyers->count())] ?? $user;

            $this->upsertOrder(
                sprintf('SO-%03d', $index + 1),
                Order::factory()
                    ->between($buyer, $user, $gig)
                    ->make($this->statusState($index + 1)),
            );
        }
    }

    private function seedConversations(User $user): void
    {
        $counterpart = User::query()
            ->where('id', '!=', $user->id)
            ->whereHas('gigs')
            ->firstOrFail();
        $conversation = Conversation::updateOrCreate(
            ['public_id' => 'seller-thread-1'],
            [
                'buyer_id' => $counterpart->id,
                'seller_id' => $user->id,
                'subject' => 'Seeded marketplace order',
                'buyer_name' => $counterpart->name,
                'seller_name' => $user->name,
                'created_by_id' => $counterpart->id,
                'context_type' => 'order',
                'context_id' => 'SO-001',
                'status' => 'In Progress',
                'status_class' => 'status-progress',
                'priority' => 'Needs reply',
                'seller_unread_count' => 3,
                'last_message_at' => now()->subMinutes(6),
            ],
        );

        $conversation->messages()->delete();
        $conversation->messages()->createMany([
            ['sender_id' => $counterpart->id, 'recipient_id' => $user->id, 'sender_name' => $counterpart->name, 'body' => 'Can we review the package scope before the next delivery?', 'sent_at' => now()->subMinutes(8)],
            ['sender_id' => $user->id, 'recipient_id' => $counterpart->id, 'sender_name' => $user->name, 'body' => 'Yes. I will send an updated order note in the next delivery.', 'sent_at' => now()->subMinutes(6)],
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
            ['type' => 'Needs reply', 'title' => 'New buyer note', 'detail' => 'A buyer added feedback to a seeded order package.'],
            ['type' => 'Growth', 'title' => 'Gig catalog ready', 'detail' => 'Factory-backed gigs are available for marketplace testing.'],
        ] as $notification) {
            UserNotification::firstOrCreate(
                ['user_id' => $user->id, 'title' => $notification['title']],
                $notification + ['user_id' => $user->id],
            );
        }
    }

    private function seedDisputes(): void
    {
        $order = Order::where('code', 'SO-001')->first();

        if (! $order) {
            return;
        }

        $conversation = Conversation::where('public_id', 'seller-thread-1')->first();
        $dispute = Dispute::updateOrCreate(
            ['case_code' => 'DSP-0001'],
            [
                'order_id' => $order->id,
                'conversation_id' => $conversation?->id,
                'opened_by_id' => $order->buyer_id,
                'assigned_to_id' => null,
                'reason' => 'Seeded delivery scope review',
                'description' => 'Buyer and seller need an admin decision on the current seeded delivery scope.',
                'priority' => 'high',
                'status' => 'open',
                'metadata' => ['source' => 'activity-seeder'],
            ],
        );

        $dispute->activities()->firstOrCreate(
            ['type' => 'opened', 'title' => 'Dispute seeded for review'],
            [
                'actor_id' => null,
                'detail' => 'Use this case to test the persisted dispute workflow.',
            ],
        );
    }

    private function statusState(int $index): array
    {
        $status = ['Pending Requirements', 'In Progress', 'Delivered', 'Completed'][$index % 4];

        return [
            'status' => $status,
            'status_class' => match ($status) {
                'Completed' => 'status-completed',
                'Delivered', 'Pending Requirements' => 'status-delivered',
                default => 'status-progress',
            },
        ];
    }

    private function upsertOrder(string $code, Order $draft): void
    {
        Order::updateOrCreate(
            ['code' => $code],
            collect($draft->attributesToArray())
                ->except(['id', 'code', 'created_at', 'updated_at'])
                ->all(),
        );
    }
}
