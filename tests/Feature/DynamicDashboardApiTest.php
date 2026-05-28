<?php

namespace Tests\Feature;

use App\Events\NotificationCreated;
use App\Models\Conversation;
use App\Models\Gig;
use App\Models\ManualPaymentMethod;
use App\Models\Order;
use App\Models\User;
use App\Support\MarketplaceNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
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
            'due_date' => now()->addDays(3)->toDateString(),
            'price_cents' => 12500,
            'earnings_cents' => 12500,
        ]);
        $seller->forceFill(['avatar' => '/assets/img/profile_images/1.png'])->save();

        $this->actingAs($buyer)
            ->getJson("/api/orders/{$order->code}?role=buyer")
            ->assertOk()
            ->assertJsonPath('data.orderNumber', $order->code)
            ->assertJsonPath('data.counterpartyAvatar', '/assets/img/profile_images/1.png')
            ->assertJsonPath('data.deliveryDate', now()->addDays(3)->format('M j, Y'))
            ->assertJsonPath('data.timeExtension.canRequest', false);

        $this->actingAs($outsider)
            ->getJson("/api/orders/{$order->code}?role=buyer")
            ->assertForbidden();
    }

    public function test_seller_can_request_and_buyer_can_decide_time_extension(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();
        $outsider = User::factory()->create();
        $order = Order::create([
            'code' => 'EXTEND-100',
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'service' => 'Extendable delivery',
            'buyer_name' => $buyer->name,
            'seller_name' => $seller->name,
            'status' => 'In Progress',
            'status_class' => 'status-progress',
            'due_date' => now()->addDays(4)->toDateString(),
            'price_cents' => 20000,
            'earnings_cents' => 17000,
        ]);

        $this->actingAs($buyer)
            ->postJson("/api/orders/{$order->code}/time-extensions?role=buyer", [
                'days' => 2,
                'reason' => 'Need more time to test edge cases.',
            ])
            ->assertForbidden();

        $pendingId = $this->actingAs($seller)
            ->postJson("/api/orders/{$order->code}/time-extensions?role=seller", [
                'days' => 2,
                'reason' => 'Need more time to test edge cases.',
            ])
            ->assertOk()
            ->assertJsonPath('data.timeExtension.pending.days', 2)
            ->assertJsonPath('data.timeExtension.canRequest', false)
            ->json('data.timeExtension.pending.id');

        $this->assertDatabaseHas('order_activities', [
            'order_id' => $order->id,
            'type' => 'time_extension_requested',
        ]);

        $this->actingAs($outsider)
            ->postJson("/api/orders/{$order->code}/time-extensions/{$pendingId}/decision?role=buyer", [
                'decision' => 'accept',
            ])
            ->assertForbidden();

        $this->actingAs($buyer)
            ->postJson("/api/orders/{$order->code}/time-extensions/{$pendingId}/decision?role=buyer", [
                'decision' => 'accept',
            ])
            ->assertOk()
            ->assertJsonPath('data.timeExtension.latest.status', 'accepted')
            ->assertJsonPath('data.timeExtension.pending', null)
            ->assertJsonPath('data.deliveryDate', now()->addDays(6)->format('M j, Y'));

        $this->assertDatabaseHas('order_time_extension_requests', [
            'id' => $pendingId,
            'status' => 'accepted',
        ]);
        $this->assertDatabaseHas('order_activities', [
            'order_id' => $order->id,
            'type' => 'time_extension_accepted',
        ]);
    }

    public function test_private_order_notes_are_visible_only_to_their_owner(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();
        $order = Order::create([
            'code' => 'NOTE-100',
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'service' => 'Private note delivery',
            'buyer_name' => $buyer->name,
            'seller_name' => $seller->name,
            'status' => 'In Progress',
            'status_class' => 'status-progress',
            'price_cents' => 14000,
            'earnings_cents' => 11900,
        ]);

        $noteId = $this->actingAs($buyer)
            ->postJson("/api/orders/{$order->code}/private-notes?role=buyer", [
                'body' => 'Remember to verify the final ZIP file.',
            ])
            ->assertOk()
            ->assertJsonPath('data.privateNotes.0.body', 'Remember to verify the final ZIP file.')
            ->json('data.privateNotes.0.id');

        $this->actingAs($seller)
            ->getJson("/api/orders/{$order->code}?role=seller")
            ->assertOk()
            ->assertJsonPath('data.privateNotes', []);

        $this->actingAs($seller)
            ->patchJson("/api/orders/{$order->code}/private-notes/{$noteId}?role=seller", [
                'body' => 'Trying to edit buyer note.',
            ])
            ->assertForbidden();

        $this->actingAs($buyer)
            ->patchJson("/api/orders/{$order->code}/private-notes/{$noteId}?role=buyer", [
                'body' => 'Updated private reminder.',
            ])
            ->assertOk()
            ->assertJsonPath('data.privateNotes.0.body', 'Updated private reminder.');

        $this->actingAs($buyer)
            ->deleteJson("/api/orders/{$order->code}/private-notes/{$noteId}?role=buyer")
            ->assertOk()
            ->assertJsonPath('data.privateNotes', []);

        $this->assertDatabaseMissing('order_private_notes', [
            'id' => $noteId,
        ]);
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

        Http::fake([
            'api.ipinfo.io/lite/*' => Http::response([
                'country_name' => 'Bangladesh',
            ]),
        ]);
        config(['services.ipinfo.token' => 'testing-token']);

        $this->actingAs($user)
            ->withServerVariables(['REMOTE_ADDR' => '8.8.8.8'])
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
                'projects' => [[
                    'id' => 'project-1',
                    'name' => 'Dynamic marketplace build',
                    'industry' => 'Programming & Tech',
                    'expertise' => 'Laravel',
                    'duration' => '1-3 months',
                    'cost' => '1500',
                    'startedMonth' => 'May',
                    'startedYear' => '2026',
                    'image' => '/assets/img/gig_images/1.png',
                    'description' => 'Built a profile-backed marketplace flow.',
                ]],
                'workExperience' => [
                    'title' => 'Full Stack Developer',
                    'employmentType' => 'Contract',
                    'company' => 'Acme Labs',
                    'startDate' => '2025-01-15',
                    'endDate' => '2026-05-01',
                    'duration' => '1 yr 5 mos',
                    'description' => 'Delivered Laravel and React marketplace features.',
                    'skills' => ['Laravel', 'React'],
                ],
                'featuredClients' => [
                    ['id' => 'client-1', 'name' => 'Acme Labs', 'description' => 'Dashboard build'],
                ],
            ])
            ->assertSuccessful()
            ->assertJsonPath('data.title', 'Dynamic seller')
            ->assertJsonPath('data.featuredClients.0.name', 'Acme Labs');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'country' => 'Bangladesh',
        ]);

        $avatar = $this->actingAs($user)
            ->post('/api/user/avatar', [
                'avatar' => UploadedFile::fake()->image('avatar.jpg', 256, 256),
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.avatar', fn ($path) => str_starts_with($path, "/uploads/profile-images/{$user->id}/"))
            ->json('data.avatar');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'avatar' => $avatar,
        ]);

        Gig::factory()->withSeller($user)->create([
            'slug' => 'profile-review-gig',
            'rating' => 4.8,
            'reviews' => 12,
            'metadata' => [
                'reviewSample' => [
                    'name' => 'Maya Chen',
                    'country' => 'United States',
                    'rating' => 5,
                    'text' => 'Excellent delivery and clear communication.',
                ],
            ],
        ]);

        $this->getJson("/api/users/{$user->username}/profile")
            ->assertOk()
            ->assertJsonPath('data.about', 'Seller profile from the database.')
            ->assertJsonPath('data.featuredClients.0.name', 'Acme Labs')
            ->assertJsonPath('data.portfolio.title', 'Dynamic marketplace build')
            ->assertJsonPath('data.workExperience.0.role', 'Full Stack Developer')
            ->assertJsonPath('data.workExperience.0.type', 'Contract')
            ->assertJsonPath('data.workExperience.0.skills.0', 'Laravel')
            ->assertJsonPath('data.reviewsData.sample.name', 'Maya Chen')
            ->assertJsonPath('data.reviews', 12);

        $this->actingAs($user)
            ->patchJson('/api/billing/profile', [
                'fullName' => 'Billing User',
                'country' => 'Bangladesh',
                'city' => 'Dhaka',
            ])
            ->assertSuccessful()
            ->assertJsonPath('data.city', 'Dhaka');

        $this->actingAs($user)
            ->postJson('/api/billing/add-balance', [
                'amount' => 25,
                'method' => 'test_card',
                'note' => 'Feature test deposit',
            ])
            ->assertCreated()
            ->assertJsonPath('data.transaction.amount', '$25.00')
            ->assertJsonPath('data.summary.balances.balance', '$25');

        $this->assertDatabaseHas('user_wallets', [
            'user_id' => $user->id,
            'balance_cents' => 2500,
        ]);
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $user->id,
            'type' => 'deposit',
            'amount_cents' => 2500,
            'status' => 'completed',
        ]);

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

        Event::fake([NotificationCreated::class]);

        app(MarketplaceNotifier::class)->notify(
            $user,
            'Message',
            'Muted realtime message',
            'This notification should persist without broadcasting.',
        );

        Event::assertNotDispatched(NotificationCreated::class);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'title' => 'Muted realtime message',
        ]);

        $this->actingAs($user)
            ->patchJson('/api/user/settings/notifications', [
                'preferences' => [
                    'inboxMessages' => ['email' => false, 'push' => true],
                ],
                'realtimeEnabled' => true,
                'soundEnabled' => true,
            ])
            ->assertSuccessful();

        Event::fake([NotificationCreated::class]);

        app(MarketplaceNotifier::class)->notify(
            $user,
            'Message',
            'Realtime message',
            'This notification should broadcast.',
        );

        Event::assertDispatched(NotificationCreated::class);

        $this->actingAs($user)
            ->post('/api/user/settings/identity-verification', [
                'legalName' => 'Billing User',
                'documentType' => 'Passport',
                'documentReference' => 'REVIEW-1',
                'country' => 'Bangladesh',
                'document' => UploadedFile::fake()->image('passport.jpg', 640, 480),
            ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.status', 'submitted')
            ->assertJsonPath(
                'data.documentPath',
                fn ($path) => str_starts_with(
                    $path,
                    "/uploads/identity/{$user->id}/",
                ),
            );
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
