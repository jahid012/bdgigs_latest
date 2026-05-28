<?php

namespace Tests\Feature;

use App\Events\OrderStatusUpdated;
use App\Models\Dispute;
use App\Models\Gig;
use App\Models\ManualPaymentMethod;
use App\Models\ManualPaymentSubmission;
use App\Models\Order;
use App\Models\User;
use App\Models\VisitorPageView;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminPanelDynamicTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->admin = User::where('email', config('admin.email'))->firstOrFail();
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
            ->assertForbidden();
    }

    public function test_dynamic_admin_pages_render_database_records(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.users', ['q' => 'test@example.com']))
            ->assertOk()
            ->assertSee('test@example.com');

        $gigTitle = Gig::where('slug', 'demo-gig-001')->firstOrFail()->title;

        $this->actingAs($this->admin)
            ->get(route('admin.gigs', ['q' => $gigTitle]))
            ->assertOk()
            ->assertSee($gigTitle);

        $this->actingAs($this->admin)
            ->get(route('admin.orders', ['q' => 'BO-001']))
            ->assertOk()
            ->assertSee('#BO-001');

        $this->actingAs($this->admin)
            ->get(route('admin.disputes'))
            ->assertOk()
            ->assertSee('DSP-0001');
    }

    public function test_admin_dashboard_uses_chart_js_data_and_quick_actions_are_removed(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('data-admin-line-chart', false)
            ->assertDontSee('Quick actions');

        $this->actingAs($this->admin)
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

        $this->actingAs($this->admin)
            ->get(route('admin.reports', ['visitor_day' => now()->toDateString()]))
            ->assertOk()
            ->assertSee('Hourly visitors')
            ->assertSee('Visited pages')
            ->assertSee('/gigs/demo-gig-001');
    }

    public function test_admin_pages_respect_page_permissions(): void
    {
        $support = User::where('email', 'support@bdgigs.test')->firstOrFail();

        $this->actingAs($support)
            ->get(route('admin.users'))
            ->assertOk();

        $this->actingAs($support)
            ->get(route('admin.gigs'))
            ->assertForbidden();
    }

    public function test_admin_lists_support_search_and_filters(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.users', ['q' => 'demo-seller-02', 'type' => 'sellers']))
            ->assertOk()
            ->assertSee('demo-seller-02@bdgigs.test')
            ->assertDontSee('test@example.com');

        $gig = Gig::where('slug', 'demo-gig-002')->firstOrFail();

        $this->actingAs($this->admin)
            ->get(route('admin.gigs', ['q' => $gig->title]))
            ->assertOk()
            ->assertSee($gig->title);

        $this->actingAs($this->admin)
            ->get(route('admin.orders', ['status' => 'delivered']))
            ->assertOk()
            ->assertSee('Delivered');
    }

    public function test_admin_can_view_user_details_and_impersonate_a_marketplace_user(): void
    {
        $user = User::factory()->create([
            'name' => 'Impersonated Buyer',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertSee('Impersonated Buyer')
            ->assertSee('Login as this user');

        $this->actingAs($this->admin)
            ->post(route('admin.users.impersonate', $user))
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);

        $this->post(route('admin.impersonation.stop'))
            ->assertRedirect(route('admin.users.show', $user));

        $this->assertAuthenticatedAs($this->admin);
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

        $this->actingAs($this->admin)
            ->post(route('admin.users.verify', $user))
            ->assertRedirect();

        $this->assertSame('verified', $user->fresh()->verification_status);

        $this->actingAs($this->admin)
            ->post(route('admin.users.suspend', $user))
            ->assertRedirect();

        $this->assertNotNull($user->fresh()->suspended_at);

        $this->actingAs($this->admin)
            ->post(route('admin.users.restore', $user))
            ->assertRedirect();

        $this->assertNull($user->fresh()->suspended_at);
    }

    public function test_admin_can_update_gig_status(): void
    {
        $gig = Gig::where('slug', 'demo-gig-001')->firstOrFail();

        $this->actingAs($this->admin)
            ->get(route('admin.gigs.show', $gig))
            ->assertOk()
            ->assertSee($gig->title)
            ->assertSee('Moderation');

        $this->actingAs($this->admin)
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

        $this->actingAs($this->admin)
            ->get(route('admin.gigs'))
            ->assertOk()
            ->assertDontSee($gig->title);

        $this->actingAs($this->admin)
            ->get(route('admin.gigs', ['status' => 'deleted']))
            ->assertOk()
            ->assertSee($gig->title)
            ->assertSee('Deleted');

        $this->actingAs($this->admin)
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

        $this->actingAs($this->admin)
            ->get(route('admin.orders.show', $order->code))
            ->assertOk()
            ->assertSee('Order #'.$order->code)
            ->assertSee('Order action');

        $this->actingAs($this->admin)
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

        $this->actingAs($this->admin)
            ->get(route('admin.disputes', ['status' => 'all']))
            ->assertOk()
            ->assertSee('Disputes pagination');

        $this->actingAs($this->admin)
            ->post(route('admin.orders.disputes.store', $order), [
                'reason' => 'Admin delivery review',
                'description' => 'The buyer asked for a scope decision.',
                'priority' => 'critical',
            ])
            ->assertRedirect();

        $dispute = Dispute::where('order_id', $order->id)
            ->where('reason', 'Admin delivery review')
            ->firstOrFail();

        $this->actingAs($this->admin)
            ->get(route('admin.disputes.show', $dispute))
            ->assertOk()
            ->assertSee($dispute->case_code)
            ->assertSee('Case action');

        $this->actingAs($this->admin)
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
            'assigned_to_id' => $this->admin->id,
            'resolved_by_id' => $this->admin->id,
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

        $this->actingAs($this->admin)
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

        $this->actingAs($this->admin)
            ->get(route('admin.withdrawals'))
            ->assertOk()
            ->assertSee($withdrawalCode);

        $this->actingAs($this->admin)
            ->patch(route('admin.withdrawals.review', $withdrawal), [
                'action' => 'approve',
                'note' => 'Seller payout details checked.',
            ])
            ->assertRedirect();

        $this->assertSame('approved', $withdrawal->fresh()->status);

        $this->actingAs($this->admin)
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
