<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Gig;
use App\Models\ManualPaymentMethod;
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
            'buyer_name' => $buyer->name,
            'seller_name' => $seller->name,
            'status' => 'Open',
            'status_class' => 'status-progress',
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
            ->assertNoContent();
    }

    public function test_profile_conversations_resolve_public_username_slugs(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create([
            'name' => 'Username Seller',
        ]);
        $seller->sellerProfile()->create([
            'professional_title' => 'Messageable seller',
        ]);

        $this->actingAs($buyer)
            ->postJson('/api/conversations', [
                'contextType' => 'profile',
                'contextId' => $seller->username,
                'targetSlug' => $seller->username,
                'message' => 'Can we discuss your seller profile?',
            ])
            ->assertSuccessful()
            ->assertJsonPath('data.counterpart.name', $seller->name);
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
            ->assertSuccessful()
            ->assertJsonPath('data.overview', 'Buyer overview');

        $this->actingAs($user)
            ->patchJson('/api/user/profile/seller', [
                'title' => 'Dynamic seller',
                'about' => 'Seller profile from the database.',
                'skills' => ['Laravel'],
            ])
            ->assertSuccessful()
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
            ->assertSuccessful()
            ->assertJsonPath('data.city', 'Dhaka');

        $this->actingAs($user)
            ->patchJson('/api/user/settings/notifications', [
                'preferences' => [
                    'inboxMessages' => ['email' => false, 'push' => true],
                ],
                'realtimeEnabled' => false,
                'soundEnabled' => true,
            ])
            ->assertSuccessful()
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

    public function test_manual_checkout_creates_reviewable_order_from_gig_package(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();
        $gig = Gig::factory()->withSeller($seller)->create([
            'slug' => 'checkout-gig',
            'status' => 'Published',
        ]);
        $method = ManualPaymentMethod::create([
            'name' => 'Test transfer',
            'account_name' => 'bdgigs',
            'account_number' => 'TEST-001',
            'instructions' => 'Submit a transaction ID.',
            'active' => true,
        ]);

        $orderCode = $this->actingAs($buyer)
            ->postJson("/api/gigs/{$gig->slug}/manual-checkout", [
                'packageId' => 'basic',
                'manualPaymentMethodId' => $method->id,
                'reference' => 'TX-100',
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'Pending Payment Review')
            ->json('data.orderNumber');

        $this->assertDatabaseHas('orders', [
            'code' => $orderCode,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'status' => 'Pending Payment Review',
        ]);
        $this->assertDatabaseHas('manual_payment_submissions', [
            'buyer_id' => $buyer->id,
            'reference' => 'TX-100',
            'status' => 'pending',
        ]);
    }

    public function test_seller_manual_withdrawal_reserves_and_releases_available_balance(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        Order::create([
            'code' => 'WITHDRAW-100',
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'service' => 'Withdrawable delivery',
            'buyer_name' => $buyer->name,
            'seller_name' => $seller->name,
            'status' => 'Delivered',
            'status_class' => 'status-delivered',
            'price_cents' => 12000,
            'earnings_cents' => 10000,
        ]);

        $methodId = $this->actingAs($seller)
            ->postJson('/api/seller/payout-methods', [
                'type' => 'bank',
                'label' => 'Seller bank',
                'accountHolder' => 'Seller Holder',
                'accountNumber' => 'BANK-001',
            ])
            ->assertCreated()
            ->json('data.id');

        $withdrawalCode = $this->actingAs($seller)
            ->postJson('/api/seller/withdrawals', [
                'payoutMethodId' => $methodId,
                'amount' => 60,
                'note' => 'Please pay manually.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.statusKey', 'pending')
            ->json('data.code');

        $this->actingAs($seller)
            ->getJson('/api/seller/earnings')
            ->assertOk()
            ->assertJsonPath('data.summary.availableFunds', '$40')
            ->assertJsonPath('data.summary.clearing', '$60');

        $this->actingAs($seller)
            ->postJson('/api/seller/withdrawals', [
                'payoutMethodId' => $methodId,
                'amount' => 50,
            ])
            ->assertStatus(422);

        $this->actingAs($seller)
            ->postJson("/api/seller/withdrawals/{$withdrawalCode}/cancel")
            ->assertOk()
            ->assertJsonPath('data.statusKey', 'cancelled');

        $this->actingAs($seller)
            ->getJson('/api/seller/earnings')
            ->assertOk()
            ->assertJsonPath('data.summary.availableFunds', '$100');
    }
}
