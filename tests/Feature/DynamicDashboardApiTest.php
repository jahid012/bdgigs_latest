<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DynamicDashboardApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_user_dashboard_saved_services_and_billing_are_honest(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/user/dashboard?variant=buyer')
            ->assertOk()
            ->assertJsonPath('data.orders', [])
            ->assertJsonPath('data.messages', [])
            ->assertJsonPath('data.stats.0.value', 0);

        $this->actingAs($user)
            ->getJson('/api/saved-services')
            ->assertOk()
            ->assertExactJson(['data' => []]);

        $this->actingAs($user)
            ->getJson('/api/billing/summary')
            ->assertOk()
            ->assertJsonPath('data.history', [])
            ->assertJsonPath('data.balances.balance', '$0');
    }

    public function test_order_details_are_available_only_to_order_participants(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();
        $outsider = User::factory()->create();
        $order = Order::create([
            'code' => 'ACCESS-100',
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'service' => 'Authorized detail',
            'buyer_name' => $buyer->name,
            'seller_name' => $seller->name,
            'status' => 'In Progress',
            'status_class' => 'status-progress',
            'price_cents' => 12500,
            'earnings_cents' => 12500,
        ]);

        $this->actingAs($buyer)
            ->getJson("/api/orders/{$order->code}?role=buyer")
            ->assertOk()
            ->assertJsonPath('data.orderNumber', $order->code);

        $this->actingAs($outsider)
            ->getJson("/api/orders/{$order->code}?role=buyer")
            ->assertForbidden();
    }

    public function test_conversation_messages_can_be_saved_and_listed_per_user(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();
        $conversation = Conversation::create([
            'public_id' => 'save-thread',
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'created_by_id' => $buyer->id,
            'subject' => 'Saved messages',
        ]);
        $conversation->participants()->create([
            'user_id' => $buyer->id,
            'context_role' => 'buying',
        ]);
        $conversation->participants()->create([
            'user_id' => $seller->id,
            'context_role' => 'selling',
        ]);
        $message = $conversation->messages()->create([
            'sender_id' => $seller->id,
            'recipient_id' => $buyer->id,
            'sender_name' => $seller->name,
            'body' => 'Keep this detail.',
            'sent_at' => now(),
        ]);

        $this->actingAs($buyer)
            ->postJson("/api/messages/{$message->id}/save")
            ->assertOk()
            ->assertJsonPath('data.saved', true);

        $this->actingAs($buyer)
            ->getJson('/api/conversations/save-thread/saved-messages')
            ->assertOk()
            ->assertJsonFragment(['text' => 'Keep this detail.']);

        $this->actingAs($buyer)
            ->deleteJson("/api/messages/{$message->id}/save")
            ->assertOk();
    }

    public function test_profiles_billing_and_settings_persist_real_user_data(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($user)
            ->patchJson('/api/user/profile/buyer', [
                'overview' => 'Buyer overview',
                'timezone' => 'Asia/Dhaka',
                'languages' => ['English'],
            ])
            ->assertOk()
            ->assertJsonPath('data.overview', 'Buyer overview');

        $this->actingAs($user)
            ->patchJson('/api/user/profile/seller', [
                'title' => 'Dynamic seller',
                'about' => 'Seller profile from the database.',
                'skills' => ['Laravel'],
            ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Dynamic seller');

        $this->getJson("/api/users/{$user->username}/profile")
            ->assertOk()
            ->assertJsonPath('data.about', 'Seller profile from the database.');

        $this->actingAs($user)
            ->patchJson('/api/billing/profile', [
                'fullName' => 'Billing User',
                'country' => 'Bangladesh',
                'city' => 'Dhaka',
            ])
            ->assertOk()
            ->assertJsonPath('data.city', 'Dhaka');

        $this->actingAs($user)
            ->patchJson('/api/user/settings/notifications', [
                'preferences' => [
                    'inboxMessages' => ['email' => false, 'push' => true],
                ],
                'realtimeEnabled' => false,
                'soundEnabled' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.realtimeEnabled', false);

        $this->actingAs($user)
            ->postJson('/api/user/settings/identity-verification', [
                'legalName' => 'Billing User',
                'documentType' => 'Passport',
                'documentReference' => 'REVIEW-1',
                'country' => 'Bangladesh',
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'review');
    }
}
