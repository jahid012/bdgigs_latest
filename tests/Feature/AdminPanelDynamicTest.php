<?php

namespace Tests\Feature;

use App\Events\OrderStatusUpdated;
use App\Models\Admin;
use App\Models\Dispute;
use App\Models\Gig;
use App\Models\ManualPaymentMethod;
use App\Models\ManualPaymentSubmission;
use App\Models\ModerationReport;
use App\Models\Order;
use App\Models\User;
use App\Models\VisitorPageView;
use App\Models\WithdrawalRequest;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminPanelDynamicTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->admin = Admin::where('email', config('admin.email'))->firstOrFail();
    }

    public function test_admin_login_ignores_marketplace_device_security_listener(): void
    {
        $this->withHeader('User-Agent', 'Mozilla/5.0 AdminPanelLogin')
            ->post(route('admin.login.submit'), [
                'email' => $this->admin->email,
                'password' => config('admin.password'),
                'remember' => true,
            ])
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs($this->admin, 'admin');
    }

    public function test_guest_is_redirected_and_non_admin_is_forbidden(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));

        $user = User::create([
            'name' => 'Regular Buyer',
            'email' => 'regular@example.com',
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));

        $adminWithoutAccess = Admin::create([
            'name' => 'No Access Admin',
            'email' => 'no-access-admin@example.com',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);

        $this->actingAs($adminWithoutAccess, 'admin')
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_dynamic_admin_pages_render_database_records(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.users', ['q' => 'test@example.com']))
            ->assertOk()
            ->assertSee('test@example.com');

        $gigTitle = Gig::where('slug', 'demo-gig-001')->firstOrFail()->title;

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.gigs', ['q' => $gigTitle]))
            ->assertOk()
            ->assertSee($gigTitle);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders', ['q' => 'BO-001']))
            ->assertOk()
            ->assertSee('#BO-001');

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.disputes'))
            ->assertOk()
            ->assertSee('DSP-0001');
    }

    public function test_admin_dashboard_uses_chart_js_data_and_quick_actions_are_removed(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('data-admin-line-chart', false)
            ->assertDontSee('Quick actions');

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.settings'))
            ->assertOk()
            ->assertSee('admin-settings-actions', false)
            ->assertDontSee('Quick actions');
    }

    public function test_reports_show_human_visitor_pages(): void
    {
        VisitorPageView::create([
            'visitor_id' => 'admin-report-visitor',
            'path' => '/gigs/demo-gig-001',
            'page_title' => 'Demo gig details',
            'user_agent' => 'Mozilla/5.0 AppleWebKit/537.36 Chrome/125 Safari/537.36',
            'is_bot' => false,
            'visited_at' => now(),
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.reports', ['visitor_day' => now()->toDateString()]))
            ->assertOk()
            ->assertSee('Hourly visitors')
            ->assertSee('Visited pages')
            ->assertSee('/gigs/demo-gig-001');
    }

    public function test_admin_pages_respect_page_permissions(): void
    {
        $support = Admin::where('email', 'support@bdgigs.test')->firstOrFail();

        $this->actingAs($support, 'admin')
            ->get(route('admin.users'))
            ->assertOk();

        $this->actingAs($support, 'admin')
            ->get(route('admin.gigs'))
            ->assertForbidden();
    }

    public function test_admin_lists_support_search_and_filters(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.users', ['q' => 'demo-seller-02', 'type' => 'sellers']))
            ->assertOk()
            ->assertSee('demo-seller-02@bdgigs.test')
            ->assertDontSee('test@example.com');

        $gig = Gig::where('slug', 'demo-gig-002')->firstOrFail();

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.gigs', ['q' => $gig->title]))
            ->assertOk()
            ->assertSee($gig->title);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders', ['status' => 'delivered']))
            ->assertOk()
            ->assertSee('Delivered');
    }

    public function test_admin_user_index_supports_extended_filters_and_bulk_actions(): void
    {
        $user = User::factory()->unverified()->create([
            'name' => 'Bulk Managed Seller',
            'email' => 'bulk-managed-seller@example.com',
            'profile_type' => 'seller',
            'seller_status' => 'pending',
            'verification_status' => 'submitted',
            'country' => 'Bangladesh',
            'last_seen_at' => now(),
            'created_at' => now()->subDays(2),
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.users', [
                'q' => 'bulk-managed',
                'type' => 'sellers',
                'status' => 'submitted',
                'seller_status' => 'pending',
                'email' => 'unverified',
                'country' => 'Bangladesh',
                'activity' => 'active_7d',
                'joined' => '7d',
                'sort' => 'name',
            ]))
            ->assertOk()
            ->assertSee('admin-user-table', false)
            ->assertSee('Bulk actions')
            ->assertSee('Seller state')
            ->assertSee($user->email);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.users.bulk'), [
                'bulk_action' => 'verify',
                'users' => [$user->id],
            ])
            ->assertRedirect();

        $this->assertSame('verified', $user->fresh()->verification_status);
        $this->assertNotNull($user->fresh()->email_verified_at);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.users.bulk'), [
                'bulk_action' => 'suspend',
                'users' => [$user->id],
                'reason' => 'Bulk trust review.',
            ])
            ->assertRedirect();

        $this->assertNotNull($user->fresh()->suspended_at);
        $this->assertDatabaseHas('account_status_events', [
            'user_id' => $user->id,
            'actor_admin_id' => $this->admin->id,
            'event_type' => 'account_suspended',
        ]);
    }

    public function test_admin_gig_index_uses_data_table_filters_and_bulk_actions(): void
    {
        $gig = Gig::where('slug', 'demo-gig-001')->firstOrFail();
        $gig->forceFill([
            'featured' => false,
            'price_cents' => 12000,
            'delivery_days' => 5,
            'status' => 'Published',
            'status_class' => 'status-completed',
        ])->save();

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.gigs', [
                'status' => 'published',
                'category' => $gig->category_label,
                'seller' => $gig->seller_name,
                'featured' => 'not_featured',
                'price' => '50_150',
                'delivery' => 'standard',
                'sort' => 'price_high',
            ]))
            ->assertOk()
            ->assertSee('admin-gig-table', false)
            ->assertSee('Bulk actions')
            ->assertSee('Moderation note')
            ->assertSee($gig->title)
            ->assertDontSee('Review checklist');

        $this->actingAs($this->admin, 'admin')
            ->patch(route('admin.gigs.bulk'), [
                'bulk_action' => 'feature',
                'gigs' => [$gig->slug],
            ])
            ->assertRedirect();

        $this->assertTrue($gig->fresh()->featured);
        $this->assertDatabaseHas('gig_moderation_events', [
            'gig_id' => $gig->id,
            'actor_admin_id' => $this->admin->id,
            'event_type' => 'gig_featured',
        ]);
    }

    public function test_admin_operational_queues_use_tables_filters_modals_and_bulk_actions(): void
    {
        $buyer = User::factory()->create(['name' => 'Bulk Queue Buyer']);
        $seller = User::factory()->create([
            'name' => 'Bulk Queue Seller',
            'profile_type' => 'seller',
            'seller_status' => 'pending',
            'country' => 'Bangladesh',
            'last_seen_at' => now(),
        ]);
        $order = Order::create([
            'code' => 'BULK-ORDER-1',
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'service' => 'Bulk queue order',
            'buyer_name' => $buyer->name,
            'seller_name' => $seller->name,
            'status' => 'In Progress',
            'status_class' => 'status-progress',
            'payment_status' => 'pending',
            'price_cents' => 12500,
            'earnings_cents' => 10000,
            'due_date' => now()->addDays(2),
        ]);
        $method = ManualPaymentMethod::query()->firstOrFail();
        $submission = ManualPaymentSubmission::create([
            'order_id' => $order->id,
            'manual_payment_method_id' => $method->id,
            'buyer_id' => $buyer->id,
            'amount_cents' => 12500,
            'currency' => 'USD',
            'reference' => 'BULK-PAY-1',
            'status' => 'pending',
        ]);
        $withdrawal = WithdrawalRequest::create([
            'code' => 'BULK-WD-1',
            'seller_id' => $seller->id,
            'amount_cents' => 9000,
            'currency' => 'USD',
            'payout_snapshot' => [
                'label' => 'Wallet',
                'accountNumber' => 'WALLET-BULK-1',
            ],
            'status' => 'pending',
        ]);
        $dispute = Dispute::factory()->create([
            'order_id' => $order->id,
            'case_code' => 'DSP-BULK1',
            'status' => 'open',
            'priority' => 'normal',
        ]);
        $report = ModerationReport::create([
            'code' => 'RPT-BULK1',
            'reporter_id' => $buyer->id,
            'reported_user_id' => $seller->id,
            'type' => 'user',
            'status' => 'pending',
            'reason' => 'Bulk report review',
            'description' => 'Needs a trust review.',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders', ['q' => 'BULK-ORDER-1', 'payment' => 'pending', 'amount' => '50_200']))
            ->assertOk()
            ->assertSee('admin-order-table', false)
            ->assertSee('Bulk actions')
            ->assertSee('BULK-ORDER-1');

        $this->actingAs($this->admin, 'admin')
            ->patch(route('admin.orders.bulk'), [
                'orders' => [$order->code],
                'status' => 'Delivered',
            ])
            ->assertRedirect();

        $this->assertSame('Delivered', $order->fresh()->status);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.manual-payments', ['q' => 'BULK-PAY-1', 'method' => $method->id, 'amount' => '50_200']))
            ->assertOk()
            ->assertSee('admin-payment-table', false)
            ->assertSee('data-admin-modal-open', false)
            ->assertSee('BULK-PAY-1');

        $this->actingAs($this->admin, 'admin')
            ->patch(route('admin.manual-payments.bulk'), [
                'bulk_action' => 'approve',
                'submissions' => [$submission->id],
                'note' => 'Bulk reference checked.',
            ])
            ->assertRedirect();

        $this->assertSame('approved', $submission->fresh()->status);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.withdrawals', ['q' => 'BULK-WD-1', 'seller' => 'Bulk Queue Seller', 'amount' => '50_200']))
            ->assertOk()
            ->assertSee('admin-withdrawal-table', false)
            ->assertSee('BULK-WD-1');

        $this->actingAs($this->admin, 'admin')
            ->patch(route('admin.withdrawals.bulk'), [
                'bulk_action' => 'approve',
                'withdrawals' => [$withdrawal->code],
                'note' => 'Bulk payout details checked.',
            ])
            ->assertRedirect();

        $this->assertSame('approved', $withdrawal->fresh()->status);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.disputes', ['q' => 'DSP-BULK1', 'assignee' => 'unassigned', 'age' => '7d']))
            ->assertOk()
            ->assertSee('admin-dispute-table', false)
            ->assertSee('DSP-BULK1');

        $this->actingAs($this->admin, 'admin')
            ->patch(route('admin.disputes.bulk'), [
                'bulk_action' => 'set_priority',
                'disputes' => [$dispute->case_code],
                'priority' => 'critical',
                'note' => 'Escalated from bulk queue.',
            ])
            ->assertRedirect();

        $this->assertSame('critical', $dispute->fresh()->priority);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.moderation-reports', ['q' => 'RPT-BULK1', 'assignee' => 'unassigned', 'age' => '7d']))
            ->assertOk()
            ->assertSee('admin-report-table', false)
            ->assertSee('RPT-BULK1');

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.moderation-reports.show', $report))
            ->assertOk()
            ->assertSee('report-status-modal', false);

        $this->actingAs($this->admin, 'admin')
            ->patch(route('admin.moderation-reports.bulk'), [
                'reports' => [$report->code],
                'status' => 'reviewing',
                'note' => 'Bulk trust review started.',
            ])
            ->assertRedirect();

        $this->assertSame('reviewing', $report->fresh()->status);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.seller-applications', ['q' => 'Bulk Queue Seller', 'country' => 'Bangladesh', 'activity' => '7d']))
            ->assertOk()
            ->assertSee('admin-seller-table', false)
            ->assertSee('Bulk Queue Seller');

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.seller-applications.show', $seller))
            ->assertOk()
            ->assertSee('seller-approve-modal', false)
            ->assertSee('seller-reject-modal', false);

        $this->actingAs($this->admin, 'admin')
            ->patch(route('admin.seller-applications.bulk'), [
                'bulk_action' => 'approve',
                'sellers' => [$seller->id],
                'reason' => 'Bulk application approved.',
            ])
            ->assertRedirect();

        $this->assertSame('approved', $seller->fresh()->seller_status);
    }

    public function test_admin_can_view_user_details_and_impersonate_a_marketplace_user(): void
    {
        $user = User::factory()->create([
            'name' => 'Impersonated Buyer',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertSee('Impersonated Buyer')
            ->assertSee('Login as this user')
            ->assertSee('data-admin-user-action-modal', false)
            ->assertSee('Open an action, review the impact, then confirm.');

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.users.impersonate', $user))
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user, 'web');

        $this->post(route('admin.impersonation.stop'))
            ->assertRedirect(route('admin.users.show', $user));

        $this->assertAuthenticatedAs($this->admin, 'admin');
        $this->assertGuest('web');
    }

    public function test_admin_can_verify_suspend_and_restore_users(): void
    {
        $user = User::create([
            'name' => 'Review Seller',
            'email' => 'review-seller@example.com',
            'password' => Hash::make('password'),
            'profile_type' => 'seller',
            'verification_status' => 'review',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.users.verify', $user))
            ->assertRedirect();

        $this->assertSame('verified', $user->fresh()->verification_status);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.users.suspend', $user))
            ->assertRedirect();

        $this->assertNotNull($user->fresh()->suspended_at);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.users.restore', $user))
            ->assertRedirect();

        $this->assertNull($user->fresh()->suspended_at);
    }

    public function test_admin_can_update_gig_status(): void
    {
        $gig = Gig::where('slug', 'demo-gig-001')->firstOrFail();

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.gigs.show', $gig))
            ->assertOk()
            ->assertSee($gig->title)
            ->assertSee('Moderation')
            ->assertSee('admin-toggle-button', false)
            ->assertSee('data-admin-moderation-modal', false)
            ->assertSee('Open an action, review the impact, then confirm.');

        $this->actingAs($this->admin, 'admin')
            ->patch(route('admin.gigs.status', $gig), [
                'action' => 'reject',
                'reason' => 'The service needs clearer scope before approval.',
            ])
            ->assertRedirect();

        $gig->refresh();

        $this->assertSame('rejected', $gig->status);
        $this->assertSame('status-cancelled', $gig->status_class);
    }

    public function test_admin_deleted_gig_filter_shows_seller_soft_deletes(): void
    {
        $seller = User::factory()->create();
        $gig = Gig::factory()->withSeller($seller)->create([
            'slug' => 'soft-deleted-admin-gig',
            'title' => 'Soft Deleted Admin Gig',
        ]);
        $gig->delete();

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.gigs'))
            ->assertOk()
            ->assertDontSee($gig->title);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.gigs', ['status' => 'deleted']))
            ->assertOk()
            ->assertSee($gig->title)
            ->assertSee('Deleted');

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.gigs.show', $gig->slug))
            ->assertOk()
            ->assertSee('soft deleted');
    }

    public function test_admin_can_update_order_status_and_dispatch_event(): void
    {
        Event::fake([OrderStatusUpdated::class]);

        $buyer = User::create([
            'name' => 'Buyer Event',
            'email' => 'buyer-event@example.com',
            'password' => Hash::make('password'),
        ]);
        $seller = User::create([
            'name' => 'Seller Event',
            'email' => 'seller-event@example.com',
            'password' => Hash::make('password'),
            'profile_type' => 'seller',
        ]);
        $order = Order::create([
            'code' => 'EVT-1001',
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'service' => 'Evented order',
            'buyer_name' => $buyer->name,
            'seller_name' => $seller->name,
            'status' => 'Pending',
            'status_class' => 'status-delivered',
            'price_cents' => 10000,
            'earnings_cents' => 8000,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.show', $order->code))
            ->assertOk()
            ->assertSee('Order #'.$order->code)
            ->assertSee('Order action');

        $this->actingAs($this->admin, 'admin')
            ->patch(route('admin.orders.status', $order), [
                'status' => 'Delivered',
            ])
            ->assertRedirect();

        $this->assertSame('Delivered', $order->fresh()->status);
        $this->assertDatabaseHas('order_activities', [
            'order_id' => $order->id,
            'type' => 'admin_status_update',
        ]);
        Event::assertDispatched(OrderStatusUpdated::class, 2);
    }

    public function test_admin_can_open_paginate_and_resolve_a_dispute_case(): void
    {
        $order = Order::where('code', 'BO-001')->firstOrFail();

        Dispute::factory()->count(9)->create();

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.disputes', ['status' => 'all']))
            ->assertOk()
            ->assertSee('Disputes pagination');

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.disputes.store', $order), [
                'reason' => 'Admin delivery review',
                'description' => 'The buyer asked for a scope decision.',
                'priority' => 'critical',
            ])
            ->assertRedirect();

        $dispute = Dispute::where('order_id', $order->id)
            ->where('reason', 'Admin delivery review')
            ->firstOrFail();

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.disputes.show', $dispute))
            ->assertOk()
            ->assertSee($dispute->case_code)
            ->assertSee('Case action');

        $this->actingAs($this->admin, 'admin')
            ->patch(route('admin.disputes.update', $dispute), [
                'status' => 'resolved',
                'priority' => 'high',
                'assigned_to_id' => $this->admin->id,
                'resolution' => 'Admin resolved the scope disagreement.',
                'note' => 'Evidence reviewed.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('disputes', [
            'id' => $dispute->id,
            'status' => 'resolved',
            'priority' => 'high',
            'assigned_to_admin_id' => $this->admin->id,
            'resolved_by_admin_id' => $this->admin->id,
        ]);
        $this->assertDatabaseHas('dispute_activities', [
            'dispute_id' => $dispute->id,
            'type' => 'resolved',
        ]);
    }

    public function test_admin_can_approve_manual_payment_submission(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();
        $gig = Gig::factory()->withSeller($seller)->create([
            'slug' => 'admin-payment-gig',
            'status' => 'Published',
        ]);
        $method = ManualPaymentMethod::query()->firstOrFail();

        $orderCode = $this->actingAs($buyer)
            ->postJson("/api/gigs/{$gig->slug}/manual-checkout", [
                'packageId' => 'basic',
                'manualPaymentMethodId' => $method->id,
                'reference' => 'ADMIN-TX-1',
            ])
            ->assertCreated()
            ->json('data.orderNumber');
        $submission = ManualPaymentSubmission::query()
            ->whereHas('order', fn ($orders) => $orders->where('code', $orderCode))
            ->firstOrFail();

        $this->actingAs($this->admin, 'admin')
            ->patch(route('admin.manual-payments.review', $submission), [
                'decision' => 'approve',
                'note' => 'Reference checked.',
            ])
            ->assertRedirect();

        $this->assertSame('approved', $submission->fresh()->status);
        $this->assertSame('Waiting for Requirements', $submission->order->fresh()->status);
    }

    public function test_admin_can_review_and_mark_manual_withdrawal_paid(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        Order::create([
            'code' => 'ADMIN-WD-1',
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'service' => 'Manual withdrawal earning',
            'buyer_name' => $buyer->name,
            'seller_name' => $seller->name,
            'status' => 'Completed',
            'status_class' => 'status-completed',
            'price_cents' => 15000,
            'earnings_cents' => 12500,
        ]);

        $methodId = $this->actingAs($seller)
            ->postJson('/api/seller/payout-methods', [
                'type' => 'mobile_wallet',
                'label' => 'Wallet',
                'accountHolder' => $seller->name,
                'accountNumber' => 'WALLET-100',
            ])
            ->assertCreated()
            ->json('data.id');
        $withdrawalCode = $this->actingAs($seller)
            ->postJson('/api/seller/withdrawals', [
                'payoutMethodId' => $methodId,
                'amount' => 80,
            ])
            ->assertCreated()
            ->json('data.code');

        $withdrawal = \App\Models\WithdrawalRequest::where('code', $withdrawalCode)->firstOrFail();

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.withdrawals'))
            ->assertOk()
            ->assertSee($withdrawalCode);

        $this->actingAs($this->admin, 'admin')
            ->patch(route('admin.withdrawals.review', $withdrawal), [
                'action' => 'approve',
                'note' => 'Seller payout details checked.',
            ])
            ->assertRedirect();

        $this->assertSame('approved', $withdrawal->fresh()->status);

        $this->actingAs($this->admin, 'admin')
            ->patch(route('admin.withdrawals.review', $withdrawal), [
                'action' => 'mark_paid',
                'payment_reference' => 'PAYOUT-TX-100',
                'note' => 'Transfer sent.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('withdrawal_requests', [
            'code' => $withdrawalCode,
            'status' => 'paid',
            'payment_reference' => 'PAYOUT-TX-100',
        ]);
    }
}
