<?php

namespace Tests\Feature;

use App\Events\OrderStatusUpdated;
use App\Models\Gig;
use App\Models\Order;
use App\Models\User;
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
            ->get(route('admin.users'))
            ->assertOk()
            ->assertSee('test@example.com');

        $this->actingAs($this->admin)
            ->get(route('admin.gigs'))
            ->assertOk()
            ->assertSee('Modern Website Landing Page Design');

        $this->actingAs($this->admin)
            ->get(route('admin.orders'))
            ->assertOk()
            ->assertSee('#SH-1048');
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
            ->get(route('admin.users', ['q' => 'CloudPeak', 'type' => 'buyers']))
            ->assertOk()
            ->assertSee('cloudpeak@bdgigs.test')
            ->assertDontSee('test@example.com');

        $this->actingAs($this->admin)
            ->get(route('admin.gigs', ['q' => 'codecanyon']))
            ->assertOk()
            ->assertSee('codecanyon');

        $this->actingAs($this->admin)
            ->get(route('admin.orders', ['status' => 'delivered']))
            ->assertOk()
            ->assertSee('Delivered');
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
        $gig = Gig::where('slug', 'modern-website-landing-page-design')->firstOrFail();

        $this->actingAs($this->admin)
            ->patch(route('admin.gigs.status', $gig), [
                'action' => 'reject',
            ])
            ->assertRedirect();

        $gig->refresh();

        $this->assertSame('Rejected', $gig->status);
        $this->assertSame('status-cancelled', $gig->status_class);
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
            ->patch(route('admin.orders.status', $order), [
                'status' => 'Delivered',
            ])
            ->assertRedirect();

        $this->assertSame('Delivered', $order->fresh()->status);
        Event::assertDispatched(OrderStatusUpdated::class, 2);
    }
}
