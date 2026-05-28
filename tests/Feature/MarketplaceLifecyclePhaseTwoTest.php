<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\CustomOffer;
use App\Models\EmailLog;
use App\Models\Gig;
use App\Models\Message;
use App\Models\Order;
use App\Models\User;
use App\Models\UserWallet;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MarketplaceLifecyclePhaseTwoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Carbon::setTestNow(Carbon::parse('2026-05-28 10:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        File::deleteDirectory(public_path('uploads/message-attachments/phase-two-attachments'));

        parent::tearDown();
    }

    public function test_requirement_reminders_and_seller_start_work_flow_are_connected(): void
    {
        [$buyer, $seller, $gig] = $this->marketplaceUsersAndGig();
        $order = $this->orderBetween($buyer, $seller, $gig, [
            'code' => 'PHASE2-REQ',
            'status' => 'Waiting for Requirements',
            'status_class' => 'status-delivered',
            'payment_status' => 'paid',
            'created_at' => now()->subDays(2),
            'metadata' => [
                'requirements' => [
                    [
                        'id' => 'brief',
                        'question' => 'Project brief',
                        'type' => 'Free text',
                        'required' => true,
                        'answer' => '',
                        'files' => [],
                    ],
                ],
            ],
        ]);

        $this->artisan('orders:send-requirement-reminders', ['--hours' => 24])
            ->assertExitCode(0);
        $this->artisan('orders:send-requirement-reminders', ['--hours' => 24])
            ->assertExitCode(0);

        $this->assertDatabaseCount('order_reminders', 1);
        $this->assertDatabaseHas('order_reminders', [
            'order_id' => $order->id,
            'key' => 'requirements_pending_24h',
            'recipient_id' => $buyer->id,
        ]);
        $this->assertDatabaseHas('email_logs', [
            'recipient_email' => $buyer->email,
            'email_template_key' => 'requirements_pending',
            'status' => 'sent',
        ]);
        $this->assertDatabaseHas('order_activities', [
            'order_id' => $order->id,
            'type' => 'requirements_reminder_sent',
        ]);

        $this->actingAs($seller)
            ->postJson("/api/orders/{$order->code}/start-work?role=seller")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('requirements');

        $this->actingAs($buyer)
            ->postJson("/api/orders/{$order->code}/requirements?role=buyer", [
                'answers' => [
                    'brief' => 'Build the checkout automation exactly as described.',
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'Requirements Submitted');

        $this->actingAs($seller)
            ->postJson("/api/orders/{$order->code}/start-work?role=seller")
            ->assertOk()
            ->assertJsonPath('data.status', 'In Progress');

        $this->assertDatabaseHas('order_activities', [
            'order_id' => $order->id,
            'type' => 'seller_started_working',
        ]);
        $this->assertDatabaseHas('email_logs', [
            'recipient_email' => $buyer->email,
            'email_template_key' => 'seller_started_working',
            'status' => 'sent',
        ]);
    }

    public function test_deadline_overdue_and_revision_delivery_events_are_scheduled_and_logged(): void
    {
        [$buyer, $seller, $gig] = $this->marketplaceUsersAndGig();
        $deadlineOrder = $this->orderBetween($buyer, $seller, $gig, [
            'code' => 'PHASE2-DUE',
            'status' => 'In Progress',
            'payment_status' => 'paid',
            'due_date' => now()->toDateString(),
        ]);
        $overdueOrder = $this->orderBetween($buyer, $seller, $gig, [
            'code' => 'PHASE2-LATE',
            'status' => 'In Progress',
            'payment_status' => 'paid',
            'due_date' => now()->subDay()->toDateString(),
        ]);
        $revisionOrder = $this->orderBetween($buyer, $seller, $gig, [
            'code' => 'PHASE2-REV',
            'status' => 'Revision Requested',
            'status_class' => 'status-delivered',
            'payment_status' => 'paid',
            'metadata' => [
                'deliveries' => [
                    [
                        'id' => 'delivery-one',
                        'message' => 'Initial delivery.',
                        'status' => 'revision_requested',
                        'type' => 'delivery',
                        'submittedAt' => now()->subDay()->toISOString(),
                        'revisionMessage' => 'Please revise the hero section.',
                    ],
                ],
            ],
        ]);

        $this->artisan('orders:send-deadline-reminders')->assertExitCode(0);

        $this->assertDatabaseHas('order_reminders', [
            'order_id' => $deadlineOrder->id,
            'key' => 'deadline_24h',
        ]);
        $this->assertSame(
            2,
            EmailLog::where('email_template_key', 'order_deadline_reminder')
                ->whereIn('recipient_email', [$buyer->email, $seller->email])
                ->count(),
        );

        $this->artisan('orders:mark-overdue')->assertExitCode(0);

        $this->assertSame('Overdue', $overdueOrder->fresh()->status);
        $this->assertDatabaseHas('order_activities', [
            'order_id' => $overdueOrder->id,
            'type' => 'order_marked_overdue',
        ]);
        $this->assertDatabaseHas('email_logs', [
            'recipient_email' => $seller->email,
            'email_template_key' => 'order_overdue_alert',
            'status' => 'sent',
        ]);

        $this->actingAs($seller)
            ->postJson("/api/orders/{$revisionOrder->code}/deliveries?role=seller", [
                'message' => 'Revision delivery includes the updated source files.',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'Delivered');

        $this->assertDatabaseHas('order_activities', [
            'order_id' => $revisionOrder->id,
            'type' => 'revision_delivered',
        ]);
        $this->assertDatabaseHas('email_logs', [
            'recipient_email' => $buyer->email,
            'email_template_key' => 'revision_delivered',
            'status' => 'sent',
        ]);
    }

    public function test_cancellation_refund_and_custom_offer_terminal_states_work(): void
    {
        [$buyer, $seller, $gig] = $this->marketplaceUsersAndGig();
        $order = $this->orderBetween($buyer, $seller, $gig, [
            'code' => 'PHASE2-CANCEL',
            'status' => 'In Progress',
            'payment_status' => 'paid',
            'payment_method' => 'wallet_balance',
            'price_cents' => 12000,
            'earnings_cents' => 10200,
        ]);
        UserWallet::create([
            'user_id' => $buyer->id,
            'balance_cents' => 0,
            'credits_cents' => 0,
            'refunded_cents' => 0,
            'currency' => 'USD',
        ]);

        $this->actingAs($buyer)
            ->postJson("/api/orders/{$order->code}/cancellations?role=buyer", [
                'reason' => 'The scope changed and we mutually agreed to stop.',
            ])
            ->assertOk()
            ->assertJsonPath('data.cancellation.latest.status', 'cancellation_requested');

        $this->assertDatabaseHas('email_logs', [
            'recipient_email' => $seller->email,
            'email_template_key' => 'order_cancellation_requested',
            'status' => 'sent',
        ]);

        $this->actingAs($seller)
            ->postJson("/api/orders/{$order->code}/cancellations/decision?role=seller", [
                'decision' => 'accept',
                'note' => 'Accepted with full refund.',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'Cancelled')
            ->assertJsonPath('data.cancellation.status', 'cancelled')
            ->assertJsonPath('data.cancellation.refundStatus', 'processed');

        $this->assertSame('refunded', $order->fresh()->payment_status);
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $buyer->id,
            'type' => 'refund',
            'amount_cents' => 12000,
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('email_logs', [
            'recipient_email' => $buyer->email,
            'email_template_key' => 'order_cancelled',
            'status' => 'sent',
        ]);

        $conversation = $this->conversationBetween($buyer, $seller, 'phase-two-offers');
        $expiredOffer = $this->customOffer($conversation, $buyer, $seller, $gig, [
            'code' => 'OFFER-EXPIRE',
            'expires_at' => now()->subMinute(),
        ]);

        $this->artisan('custom-offers:expire')->assertExitCode(0);

        $this->assertSame('expired', $expiredOffer->fresh()->status);
        $this->assertDatabaseHas('email_logs', [
            'recipient_email' => $buyer->email,
            'email_template_key' => 'custom_offer_expired',
            'status' => 'sent',
        ]);

        $failedOffer = $this->customOffer($conversation, $buyer, $seller, $gig, [
            'code' => 'OFFER-FAILPAY',
            'price_cents' => 50000,
            'expires_at' => now()->addDay(),
        ]);

        $this->actingAs($buyer)
            ->postJson("/api/custom-offers/{$failedOffer->id}/pay")
            ->assertUnprocessable();

        $this->assertSame('payment_failed', $failedOffer->fresh()->status);
        $this->assertDatabaseHas('email_logs', [
            'recipient_email' => $seller->email,
            'email_template_key' => 'custom_offer_payment_failed',
            'status' => 'sent',
        ]);
    }

    public function test_inbox_attachment_unread_and_review_scheduled_flows_work(): void
    {
        [$buyer, $seller, $gig] = $this->marketplaceUsersAndGig();
        $conversation = $this->conversationBetween($buyer, $seller, 'phase-two-attachments');

        $this->actingAs($buyer)
            ->withHeader('Accept', 'application/json')
            ->post("/api/conversations/{$conversation->public_id}/messages", [
                'text' => 'Please review the attached brief.',
                'attachments' => [
                    UploadedFile::fake()->create('brief.pdf', 14, 'application/pdf'),
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('data.attachments.0.name', 'brief.pdf');

        $message = Message::where('conversation_id', $conversation->id)->latest()->firstOrFail();
        $message->forceFill(['sent_at' => now()->subMinutes(40), 'created_at' => now()->subMinutes(40)])->save();

        $this->assertDatabaseHas('email_logs', [
            'recipient_email' => $seller->email,
            'email_template_key' => 'attachment_received_in_conversation',
            'status' => 'sent',
        ]);

        $this->artisan('messages:send-unread-reminders', ['--minutes' => 15])
            ->assertExitCode(0);

        $this->assertNotNull($message->fresh()->email_reminder_sent_at);
        $this->assertDatabaseHas('email_logs', [
            'recipient_email' => $seller->email,
            'email_template_key' => 'unread_message_reminder',
            'status' => 'sent',
        ]);

        $reviewOrder = $this->orderBetween($buyer, $seller, $gig, [
            'code' => 'PHASE2-REVIEW',
            'status' => 'Completed',
            'status_class' => 'status-completed',
            'payment_status' => 'paid',
            'review_period_expires_at' => now()->addDay(),
        ]);

        $this->artisan('reviews:send-deadline-reminders')->assertExitCode(0);

        $this->assertDatabaseHas('order_reminders', [
            'order_id' => $reviewOrder->id,
            'key' => 'review_deadline_buyer',
            'recipient_id' => $buyer->id,
        ]);
        $this->assertDatabaseHas('email_logs', [
            'recipient_email' => $buyer->email,
            'email_template_key' => 'review_deadline_reminder',
            'status' => 'sent',
        ]);

        $expiredReviewOrder = $this->orderBetween($buyer, $seller, $gig, [
            'code' => 'PHASE2-REVIEW-EXP',
            'status' => 'Completed',
            'status_class' => 'status-completed',
            'payment_status' => 'paid',
            'review_period_expires_at' => now()->subDay(),
        ]);

        $this->artisan('reviews:expire-periods')->assertExitCode(0);

        $this->assertNotNull($expiredReviewOrder->fresh()->review_period_expired_at);
        $this->assertDatabaseHas('email_logs', [
            'recipient_email' => $seller->email,
            'email_template_key' => 'review_period_expired',
            'status' => 'sent',
        ]);

        $this->actingAs($buyer)
            ->postJson("/api/orders/{$reviewOrder->code}/reviews?role=buyer", [
                'rating' => 5,
                'comment' => 'Excellent delivery and clear communication throughout.',
            ])
            ->assertOk();

        $this->actingAs($seller)
            ->postJson("/api/orders/{$reviewOrder->code}/reviews?role=seller", [
                'rating' => 5,
                'comment' => 'Great buyer with clear requirements and fast feedback.',
            ])
            ->assertOk()
            ->assertJsonPath('data.reviewsState.visibleReviews.buyer.role', 'buyer')
            ->assertJsonPath('data.reviewsState.visibleReviews.seller.role', 'seller');

        $this->assertNotNull($reviewOrder->fresh()->reviews_visible_at);
        $this->assertDatabaseHas('email_logs', [
            'recipient_email' => $buyer->email,
            'email_template_key' => 'reviews_are_now_visible',
            'status' => 'sent',
        ]);
    }

    private function marketplaceUsersAndGig(): array
    {
        $buyer = User::factory()->create(['email' => fake()->unique()->safeEmail()]);
        $seller = User::factory()->create([
            'email' => fake()->unique()->safeEmail(),
            'profile_type' => 'seller',
        ]);
        $gig = Gig::factory()->withSeller($seller)->create([
            'title' => 'Phase two lifecycle service',
            'price_cents' => 12000,
            'requirements' => [
                ['id' => 'brief', 'label' => 'Project brief', 'required' => true],
            ],
        ]);

        return [$buyer, $seller, $gig];
    }

    private function orderBetween(User $buyer, User $seller, Gig $gig, array $attributes = []): Order
    {
        return Order::factory()
            ->between($buyer, $seller, $gig)
            ->create([
                'status' => 'In Progress',
                'status_class' => 'status-progress',
                'payment_status' => 'paid',
                'payment_method' => 'wallet_balance',
                'transaction_id' => 'WLT-PHASE2',
                'due_date' => now()->addDays(3)->toDateString(),
                'metadata' => [
                    'itemSummary' => 'Phase two package',
                    'quantity' => 1,
                    'duration' => '3 days',
                    'requirements' => [],
                ],
                ...$attributes,
            ]);
    }

    private function conversationBetween(User $buyer, User $seller, string $publicId): Conversation
    {
        $conversation = Conversation::create([
            'public_id' => $publicId,
            'created_by_id' => $buyer->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'subject' => 'Phase two conversation',
            'buyer_name' => $buyer->name,
            'seller_name' => $seller->name,
            'status' => 'Open',
            'status_class' => 'status-progress',
            'last_message_at' => now()->subHour(),
        ]);

        $conversation->participants()->create([
            'user_id' => $buyer->id,
            'context_role' => 'buying',
            'last_read_at' => now(),
            'last_seen_at' => now(),
        ]);
        $conversation->participants()->create([
            'user_id' => $seller->id,
            'context_role' => 'selling',
            'last_read_at' => now()->subDay(),
            'last_seen_at' => now()->subDay(),
        ]);

        return $conversation;
    }

    private function customOffer(Conversation $conversation, User $buyer, User $seller, Gig $gig, array $attributes = []): CustomOffer
    {
        return CustomOffer::create([
            'conversation_id' => $conversation->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'gig_id' => $gig->id,
            'code' => 'OFFER-'.Str::upper(Str::random(6)),
            'title' => 'Phase two custom offer',
            'description' => 'Custom offer for lifecycle testing.',
            'price_cents' => 5000,
            'currency' => 'USD',
            'delivery_days' => 3,
            'revisions' => '2 revisions',
            'status' => 'pending',
            'expires_at' => now()->addDay(),
            ...$attributes,
        ]);
    }
}
