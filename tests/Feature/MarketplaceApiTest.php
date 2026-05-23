<?php

namespace Tests\Feature;

use App\Events\ConversationUpdated;
use App\Events\MessageSent;
use App\Events\NotificationCreated;
use App\Models\Conversation;
use App\Models\Gig;
use App\Models\User;
use App\Models\UserNotification;
use Database\Seeders\MarketplaceDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class MarketplaceApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake([
            ConversationUpdated::class,
            MessageSent::class,
            NotificationCreated::class,
        ]);
        Queue::fake();

        $this->seed(MarketplaceDemoSeeder::class);
        $this->user = User::where('email', 'test@example.com')->firstOrFail();
    }

    public function test_user_can_login_and_fetch_session_user(): void
    {
        $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonPath('data.authenticated', true)
            ->assertJsonPath('data.name', 'Jahid');

        $this->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.authenticated', true);
    }

    public function test_user_can_register_with_email_and_password(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'New Buyer',
            'email' => 'new-buyer@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
            ->assertOk()
            ->assertJsonPath('data.authenticated', true)
            ->assertJsonPath('data.email', 'new-buyer@example.com');

        $this->assertDatabaseHas('users', [
            'email' => 'new-buyer@example.com',
        ]);
    }

    public function test_seller_can_create_and_update_own_service(): void
    {
        $payload = [
            'title' => 'Dynamic Laravel Marketplace Setup',
            'category' => 'Programming & Tech',
            'tags' => ['Laravel', 'Marketplace'],
            'packages' => [
                ['id' => 'basic', 'label' => 'Basic', 'price' => '125', 'delivery' => '3 Days Delivery'],
            ],
            'galleryImages' => ['/assets/img/gig_images/1.png'],
            'media' => [
                [
                    'type' => 'image',
                    'url' => '/assets/img/gig_images/1.png',
                    'altText' => 'Dynamic Laravel Marketplace Setup preview',
                    'primary' => true,
                ],
                [
                    'type' => 'video',
                    'url' => '/uploads/gig-media/demo.mp4',
                    'thumbnailUrl' => '/assets/img/gig_images/1.png',
                    'altText' => 'Dynamic Laravel Marketplace Setup video',
                ],
            ],
            'description' => 'Database backed marketplace setup.',
            'faqs' => [
                ['question' => 'Do you support Laravel?', 'answer' => 'Yes.'],
            ],
        ];

        $created = $this->actingAs($this->user)
            ->postJson('/api/seller/services', $payload)
            ->assertCreated()
            ->assertJsonPath('data.title', 'Dynamic Laravel Marketplace Setup')
            ->assertJsonPath('data.media.0.type', 'image')
            ->assertJsonPath('data.videos.0.type', 'video')
            ->json('data');

        $this->actingAs($this->user)
            ->patchJson("/api/seller/services/{$created['id']}", [
                ...$payload,
                'title' => 'Dynamic Laravel Marketplace Build',
            ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Dynamic Laravel Marketplace Build');

        $this->assertDatabaseHas('gigs', [
            'slug' => $created['id'],
            'title' => 'Dynamic Laravel Marketplace Build',
        ]);
        $this->assertDatabaseHas('gig_media', [
            'url' => '/assets/img/gig_images/1.png',
            'type' => 'image',
        ]);
    }

    public function test_seller_can_upload_gig_media_before_saving_service(): void
    {
        $this->actingAs($this->user)
            ->post('/api/seller/services/media', [
                'type' => 'image',
                'file' => UploadedFile::fake()->create('preview.jpg', 64, 'image/jpeg'),
            ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.type', 'image')
            ->assertJsonPath('data.originalName', 'preview.jpg');
    }

    public function test_user_cannot_update_another_sellers_service(): void
    {
        $other = User::create([
            'name' => 'Other Seller',
            'email' => 'other-seller@example.com',
            'password' => Hash::make('password'),
        ]);
        $gig = Gig::create([
            'seller_id' => $other->id,
            'slug' => 'private-service',
            'title' => 'Private Service',
            'seller_name' => $other->name,
            'category_label' => 'Private',
            'price_cents' => 1000,
        ]);

        $this->actingAs($this->user)
            ->patchJson("/api/seller/services/{$gig->slug}", [
                'title' => 'Should Not Update',
            ])
            ->assertForbidden();
    }

    public function test_seller_can_pause_preview_activate_and_soft_delete_own_service(): void
    {
        $gig = Gig::factory()->withSeller($this->user)->create([
            'slug' => 'seller-lifecycle-gig',
            'status' => 'Live',
        ]);

        $this->actingAs($this->user)
            ->patchJson("/api/seller/services/{$gig->slug}/status", [
                'action' => 'pause',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'Paused')
            ->assertJsonPath('data.statusKey', 'paused');

        $this->actingAs($this->user)
            ->getJson("/api/gigs/{$gig->slug}")
            ->assertOk()
            ->assertJsonPath('data.id', $gig->slug);

        $this->actingAs($this->user)
            ->patchJson("/api/seller/services/{$gig->slug}/status", [
                'action' => 'activate',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'Live');

        $this->actingAs($this->user)
            ->deleteJson("/api/seller/services/{$gig->slug}")
            ->assertNoContent();

        $this->assertSoftDeleted('gigs', [
            'id' => $gig->id,
        ]);
    }

    public function test_gigs_and_saved_services_are_available(): void
    {
        $gig = Gig::query()
            ->where('seller_id', '!=', $this->user->id)
            ->firstOrFail();

        $this->actingAs($this->user)
            ->getJson('/api/gigs')
            ->assertOk()
            ->assertJsonFragment(['id' => $gig->slug]);

        $this->actingAs($this->user)
            ->postJson("/api/saved-services/{$gig->slug}")
            ->assertOk()
            ->assertJsonPath('data.id', $gig->slug);

        $this->actingAs($this->user)
            ->getJson('/api/saved-services')
            ->assertOk()
            ->assertJsonFragment(['id' => $gig->slug]);
    }

    public function test_orders_conversations_and_messages_are_authorized(): void
    {
        $conversation = Conversation::where('public_id', 'seller-thread-1')->firstOrFail();

        $this->actingAs($this->user)
            ->getJson('/api/orders?role=seller')
            ->assertOk()
            ->assertJsonFragment(['id' => '#SO-001']);

        $this->actingAs($this->user)
            ->getJson('/api/conversations?role=seller')
            ->assertOk()
            ->assertJsonFragment(['id' => 'seller-thread-1']);

        $this->actingAs($this->user)
            ->postJson("/api/conversations/{$conversation->public_id}/messages", [
                'text' => 'I uploaded the next milestone.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.text', 'I uploaded the next milestone.');

        $outsider = User::create([
            'name' => 'Outsider',
            'email' => 'outsider@example.com',
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($outsider)
            ->getJson("/api/conversations/{$conversation->public_id}")
            ->assertForbidden();
    }

    public function test_user_can_start_gig_and_order_conversations_without_duplicates(): void
    {
        $gig = Gig::query()
            ->where('seller_id', '!=', $this->user->id)
            ->firstOrFail();

        $created = $this->actingAs($this->user)
            ->postJson('/api/conversations', [
                'targetUserId' => $gig->seller_id,
                'contextType' => 'gig',
                'contextId' => $gig->slug,
                'message' => 'Hi, I would like to discuss this WordPress service.',
            ])
            ->assertOk()
            ->assertJsonPath('data.context.type', 'gig')
            ->json('data');

        $this->assertDatabaseHas('conversation_participants', [
            'user_id' => $this->user->id,
        ]);
        $this->assertDatabaseHas('messages', [
            'body' => 'Hi, I would like to discuss this WordPress service.',
        ]);

        $this->actingAs($this->user)
            ->postJson('/api/conversations', [
                'targetUserId' => $gig->seller_id,
                'contextType' => 'gig',
                'contextId' => $gig->slug,
            ])
            ->assertOk()
            ->assertJsonPath('data.id', $created['id']);

        $this->actingAs($this->user)
            ->postJson('/api/conversations', [
                'contextType' => 'order',
                'contextId' => 'BO-001',
            ])
            ->assertOk()
            ->assertJsonPath('data.context.type', 'order');
    }

    public function test_message_send_increments_unread_and_read_clears_it(): void
    {
        $conversation = Conversation::where('public_id', 'seller-thread-1')->firstOrFail();
        $counterpart = $conversation->participants()
            ->with('user')
            ->where('user_id', '!=', $this->user->id)
            ->firstOrFail()
            ->user;

        $this->actingAs($counterpart)
            ->postJson("/api/conversations/{$conversation->public_id}/messages", [
                'text' => 'Please check this unread note.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.text', 'Please check this unread note.');

        $this->assertGreaterThan(
            0,
            $conversation->participants()
                ->where('user_id', $this->user->id)
                ->firstOrFail()
                ->unread_count,
        );

        $this->actingAs($this->user)
            ->patchJson("/api/conversations/{$conversation->public_id}/read")
            ->assertOk()
            ->assertJsonPath('data.viewerParticipant.unreadCount', 0);

        $this->assertSame(
            0,
            $conversation->participants()
                ->where('user_id', $this->user->id)
                ->firstOrFail()
                ->unread_count,
        );
    }

    public function test_presence_heartbeat_and_push_subscription_are_saved(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/push-subscriptions', [
                'token' => 'browser-token-1',
                'platform' => 'web',
            ])
            ->assertOk()
            ->assertJsonPath('data.platform', 'web');

        $this->actingAs($this->user)
            ->postJson('/api/presence/heartbeat', [
                'token' => 'browser-token-1',
            ])
            ->assertOk()
            ->assertJsonPath('data.online', true);

        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $this->user->id,
            'token' => 'browser-token-1',
            'revoked_at' => null,
        ]);
        $this->assertNotNull($this->user->fresh()->last_seen_at);
    }

    public function test_notifications_can_be_marked_as_read(): void
    {
        $notification = UserNotification::where('user_id', $this->user->id)->firstOrFail();

        $this->actingAs($this->user)
            ->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonFragment(['id' => $notification->id]);

        $this->actingAs($this->user)
            ->patchJson("/api/notifications/{$notification->id}/read")
            ->assertOk()
            ->assertJsonPath('data.id', $notification->id);

        $this->assertNotNull($notification->fresh()->read_at);
    }
}
