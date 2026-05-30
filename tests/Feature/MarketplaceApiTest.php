<?php

namespace Tests\Feature;

use App\Events\ConversationUpdated;
use App\Events\MessageSent;
use App\Events\NotificationCreated;
use App\Models\Conversation;
use App\Models\Gig;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\VisitorPageView;
use Database\Seeders\MarketplaceDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
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
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
            'remember' => true,
        ])
            ->assertOk()
            ->assertJsonPath('data.authenticated', true)
            ->assertJsonPath('data.name', 'Jahid');
        $rememberCookie = $response->getCookie(Auth::guard('web')->getRecallerName(), false);

        $this->assertNotNull($rememberCookie);
        $this->assertGreaterThan(now()->addDays(29)->timestamp, $rememberCookie->getExpiresTime());
        $this->assertLessThan(now()->addDays(31)->timestamp, $rememberCookie->getExpiresTime());

        $this->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.authenticated', true);
    }

    public function test_human_page_views_are_tracked_and_bots_are_ignored(): void
    {
        $this->withHeader('User-Agent', 'Mozilla/5.0 AppleWebKit/537.36 Chrome/125 Safari/537.36')
            ->postJson('/api/analytics/page-view', [
                'path' => '/gigs/demo-gig-001',
                'title' => 'Demo gig',
                'referrer' => 'https://bdgigs.test/',
                'visitorId' => 'visitor-test-1',
            ])
            ->assertOk()
            ->assertJsonPath('data.tracked', true);

        $this->withHeader('User-Agent', 'Googlebot/2.1')
            ->postJson('/api/analytics/page-view', [
                'path' => '/gigs/demo-gig-001',
                'title' => 'Demo gig',
                'visitorId' => 'visitor-test-bot',
            ])
            ->assertOk()
            ->assertJsonPath('data.tracked', false);

        $this->assertSame(1, VisitorPageView::count());
        $this->assertDatabaseHas('visitor_page_views', [
            'path' => '/gigs/demo-gig-001',
            'visitor_id' => 'visitor-test-1',
            'is_bot' => false,
        ]);
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

        $this->actingAs($this->user)
            ->post('/api/seller/services/media', [
                'type' => 'video',
                'file' => UploadedFile::fake()->create(
                    'intro.mp4',
                    512,
                    'video/mp4',
                ),
            ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.type', 'video')
            ->assertJsonPath('data.originalName', 'intro.mp4')
            ->assertJsonPath('data.thumbnailUrl', null)
            ->assertJsonPath(
                'data.url',
                fn ($path) => str_starts_with(
                    $path,
                    "/uploads/gig-media/{$this->user->id}/",
                ),
            );
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
        $this->user->forceFill(['seller_status' => 'approved'])->save();

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
            ->assertJsonPath('data.status', 'Approved')
            ->assertJsonPath('data.statusKey', 'live');

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

    public function test_presence_join_and_push_subscription_are_saved(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/push-subscriptions', [
                'token' => 'browser-token-1',
                'platform' => 'web',
            ])
            ->assertOk()
            ->assertJsonPath('data.platform', 'web');

        $this->actingAs($this->user)
            ->postJson('/api/presence/join', [
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

    public function test_pusher_webhook_member_removed_updates_last_seen_at(): void
    {
        config()->set('broadcasting.connections.pusher.key', 'test-key');
        config()->set('broadcasting.connections.pusher.secret', 'test-secret');
        config()->set('broadcasting.connections.pusher.app_id', '123');
        config()->set('broadcasting.connections.pusher.options.cluster', 'mt1');

        $payload = [
            'time_ms' => now()->timestamp * 1000,
            'events' => [
                [
                    'name' => 'member_removed',
                    'channel' => 'presence-online',
                    'user_id' => $this->user->id,
                ],
            ],
        ];

        $headers = [
            'X-Pusher-Key' => 'test-key',
            'X-Pusher-Signature' => hash_hmac('sha256', json_encode($payload), 'test-secret'),
        ];

        $this->withHeaders($headers)
            ->postJson('/api/broadcasting/webhook', $payload)
            ->assertOk()
            ->assertJsonPath('status', 'ok');

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
