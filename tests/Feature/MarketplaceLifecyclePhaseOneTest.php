<?php

namespace Tests\Feature;

use App\Models\EmailLog;
use App\Models\Gig;
use App\Models\ManualPaymentMethod;
use App\Models\ManualPaymentSubmission;
use App\Models\Order;
use App\Models\User;
use App\Models\UserWallet;
use App\Services\EmailVerificationService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MarketplaceLifecyclePhaseOneTest extends TestCase
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

    public function test_email_verification_registration_resend_and_signed_link_flow(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Verification Buyer',
            'email' => 'verification-buyer@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
            ->assertOk()
            ->assertJsonPath('data.emailVerified', false);

        $user = User::where('email', 'verification-buyer@example.com')->firstOrFail();

        $this->assertFalse($user->hasVerifiedEmail());
        $this->assertDatabaseHas('email_logs', [
            'recipient_email' => $user->email,
            'email_template_key' => 'email_verification',
            'status' => 'sent',
        ]);

        $this->actingAs($user)
            ->postJson('/api/email/verification-notification')
            ->assertOk()
            ->assertJsonPath('data.message', 'Verification email sent.');

        $this->assertGreaterThanOrEqual(
            2,
            EmailLog::where('recipient_email', $user->email)
                ->where('email_template_key', 'email_verification')
                ->count(),
        );

        $verifyUrl = app(EmailVerificationService::class)->verificationUrl($user);
        $verificationResponse = $this->get($this->pathWithQuery($verifyUrl));

        $verificationResponse->assertRedirect();
        $this->assertStringContainsString('/verify-email/success', $verificationResponse->headers->get('Location'));
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => 'email_verified',
        ]);
    }

    public function test_password_reset_and_login_security_alerts_create_transactional_logs(): void
    {
        $user = $this->verifiedUser([
            'email' => 'security-buyer@example.com',
            'password' => Hash::make('old-password'),
        ]);

        $this->postJson('/forgot-password', ['email' => $user->email])
            ->assertSuccessful();

        $this->assertDatabaseHas('email_logs', [
            'recipient_email' => $user->email,
            'email_template_key' => 'password_reset',
            'status' => 'sent',
        ]);

        $token = Password::broker()->createToken($user);

        $this->postJson('/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ])->assertSuccessful();

        $this->assertTrue(Hash::check('new-password123', $user->fresh()->password));
        $this->assertDatabaseHas('email_logs', [
            'recipient_email' => $user->email,
            'email_template_key' => 'password_changed',
            'status' => 'sent',
        ]);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->withHeader('User-Agent', 'Mozilla/5.0 PhaseOneBrowser')
            ->postJson('/api/auth/login', [
                'email' => $user->email,
                'password' => 'new-password123',
            ])
            ->assertOk()
            ->assertJsonPath('data.authenticated', true);

        $this->assertDatabaseHas('user_login_devices', [
            'user_id' => $user->id,
            'ip_address' => '203.0.113.10',
        ]);

        $this->postJson('/api/auth/logout')->assertOk();

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.44'])
            ->withHeader('User-Agent', 'Mozilla/5.0 PhaseOneBrowser NewDevice')
            ->postJson('/api/auth/login', [
                'email' => $user->email,
                'password' => 'new-password123',
            ])
            ->assertOk()
            ->assertJsonPath('data.authenticated', true);

        $this->assertDatabaseHas('email_logs', [
            'recipient_email' => $user->email,
            'email_template_key' => 'login_alert',
            'status' => 'sent',
        ]);
    }

    public function test_admin_account_status_lifecycle_records_history_notifications_and_emails(): void
    {
        $user = $this->verifiedUser(['email' => 'moderated-user@example.com']);

        $this->actingAs($this->admin)
            ->post(route('admin.users.suspend', $user), [
                'reason' => 'Risk review in progress.',
            ])
            ->assertRedirect();

        $this->assertNotNull($user->fresh()->suspended_at);
        $this->assertDatabaseHas('account_status_events', [
            'user_id' => $user->id,
            'actor_id' => $this->admin->id,
            'event_type' => 'account_suspended',
            'reason' => 'Risk review in progress.',
        ]);
        $this->assertDatabaseHas('email_logs', [
            'recipient_email' => $user->email,
            'email_template_key' => 'account_suspended',
            'status' => 'sent',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.users.restore', $user), [
                'reason' => 'Risk review cleared.',
            ])
            ->assertRedirect();

        $this->assertNull($user->fresh()->suspended_at);
        $this->assertDatabaseHas('account_status_events', [
            'user_id' => $user->id,
            'event_type' => 'account_reactivated',
            'reason' => 'Risk review cleared.',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.users.deactivate', $user), [
                'reason' => 'Owner requested closure.',
            ])
            ->assertRedirect();

        $this->assertNotNull($user->fresh()->deactivated_at);
        $this->assertDatabaseHas('account_status_events', [
            'user_id' => $user->id,
            'event_type' => 'account_deactivated',
            'reason' => 'Owner requested closure.',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => 'account_status',
        ]);
    }

    public function test_wallet_order_payment_refund_invoice_and_email_log_retry_work(): void
    {
        $buyer = $this->verifiedUser(['email' => 'wallet-buyer@example.com']);
        $seller = $this->verifiedUser([
            'email' => 'wallet-seller@example.com',
            'profile_type' => 'seller',
        ]);
        $gig = Gig::factory()->withSeller($seller)->create([
            'slug' => 'phase-one-wallet-gig',
            'title' => 'Phase one wallet service',
            'price_cents' => 15000,
            'packages' => [
                [
                    'id' => 'basic',
                    'name' => 'Basic',
                    'title' => 'Wallet package',
                    'delivery' => '3-day delivery',
                    'revisions' => '2 revisions',
                    'price' => '150',
                ],
            ],
        ]);
        UserWallet::create([
            'user_id' => $buyer->id,
            'balance_cents' => 30000,
            'credits_cents' => 0,
            'refunded_cents' => 0,
            'currency' => 'USD',
        ]);

        $orderCode = $this->actingAs($buyer)
            ->postJson("/api/gigs/{$gig->slug}/wallet-checkout", [
                'packageId' => 'basic',
            ])
            ->assertSuccessful()
            ->assertJsonPath('data.paymentStatus', 'paid')
            ->json('data.orderNumber');

        $order = Order::where('code', $orderCode)->firstOrFail();

        $this->assertSame('paid', $order->payment_status);
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $buyer->id,
            'type' => 'debit',
            'amount_cents' => -15000,
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $seller->id,
            'type' => 'pending_earning',
            'amount_cents' => 12750,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('order_invoices', [
            'order_id' => $order->id,
            'amount_cents' => 15000,
        ]);
        $this->assertDatabaseHas('order_activities', [
            'order_id' => $order->id,
            'type' => 'payment_successful',
        ]);
        $this->assertDatabaseHas('email_logs', [
            'recipient_email' => $buyer->email,
            'email_template_key' => 'payment_successful',
            'status' => 'sent',
        ]);
        $this->assertDatabaseHas('email_logs', [
            'recipient_email' => $buyer->email,
            'email_template_key' => 'invoice_receipt_email',
            'status' => 'sent',
        ]);

        $this->actingAs($buyer)
            ->getJson("/api/orders/{$order->code}/receipt")
            ->assertOk()
            ->assertJsonPath('data.order_id', $order->code)
            ->assertJsonPath('data.payment_method', 'wallet_balance');

        $this->actingAs($this->admin)
            ->post(route('admin.orders.refund', $order), [
                'amount' => '150',
                'reason' => 'Scope cancelled.',
            ])
            ->assertRedirect();

        $order->refresh();
        $this->assertSame('refunded', $order->payment_status);
        $this->assertSame(15000, $order->refund_amount_cents);
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $buyer->id,
            'type' => 'refund',
            'amount_cents' => 15000,
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('email_logs', [
            'recipient_email' => $buyer->email,
            'email_template_key' => 'order_refunded',
            'status' => 'sent',
        ]);

        $failedLog = EmailLog::create([
            'user_id' => $buyer->id,
            'email_template_key' => 'payment_failed',
            'recipient_email' => $buyer->email,
            'subject' => 'Payment failed for retry test',
            'status' => 'failed',
            'error_message' => 'SMTP timeout',
            'payload' => [
                'order_id' => $order->code,
                'order_title' => $order->service,
                'order_amount' => '$150.00',
                'action_url' => '/dashboard/payments',
            ],
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.email-logs.show', $failedLog))
            ->assertOk()
            ->assertSee('SMTP timeout');

        $this->actingAs($this->admin)
            ->post(route('admin.email-logs.retry', $failedLog))
            ->assertRedirect();

        $this->assertGreaterThanOrEqual(
            1,
            EmailLog::where('recipient_email', $buyer->email)
                ->where('email_template_key', 'payment_failed')
                ->where('status', 'sent')
                ->whereKeyNot($failedLog->id)
                ->count(),
        );
    }

    public function test_manual_payment_failure_records_failed_transaction_activity_and_email(): void
    {
        $buyer = $this->verifiedUser(['email' => 'failed-payment-buyer@example.com']);
        $seller = $this->verifiedUser([
            'email' => 'failed-payment-seller@example.com',
            'profile_type' => 'seller',
        ]);
        $gig = Gig::factory()->withSeller($seller)->create([
            'slug' => 'phase-one-failed-payment-gig',
            'title' => 'Phase one failed payment service',
            'price_cents' => 9000,
            'packages' => [
                [
                    'id' => 'basic',
                    'name' => 'Basic',
                    'title' => 'Manual package',
                    'delivery' => '2-day delivery',
                    'revisions' => '1 revision',
                    'price' => '90',
                ],
            ],
        ]);
        $method = ManualPaymentMethod::query()->firstOrFail();

        $orderCode = $this->actingAs($buyer)
            ->postJson("/api/gigs/{$gig->slug}/manual-checkout", [
                'packageId' => 'basic',
                'manualPaymentMethodId' => $method->id,
                'reference' => 'FAILED-MANUAL-REF',
            ])
            ->assertCreated()
            ->assertJsonPath('data.paymentStatus', 'pending')
            ->json('data.orderNumber');

        $submission = ManualPaymentSubmission::query()
            ->whereHas('order', fn ($orders) => $orders->where('code', $orderCode))
            ->firstOrFail();

        $this->actingAs($this->admin)
            ->patch(route('admin.manual-payments.review', $submission), [
                'decision' => 'reject',
                'note' => 'Reference could not be verified.',
            ])
            ->assertRedirect();

        $order = $submission->order->fresh();

        $this->assertSame('failed', $order->payment_status);
        $this->assertSame('Payment Rejected', $order->status);
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $buyer->id,
            'type' => 'order_payment',
            'amount_cents' => -9000,
            'status' => 'failed',
        ]);
        $this->assertDatabaseHas('order_activities', [
            'order_id' => $order->id,
            'type' => 'payment_failed',
        ]);
        $this->assertDatabaseHas('email_logs', [
            'recipient_email' => $buyer->email,
            'email_template_key' => 'payment_failed',
            'status' => 'sent',
        ]);
    }

    private function verifiedUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ], $attributes));
    }

    private function pathWithQuery(string $url): string
    {
        $parts = parse_url($url);
        $path = $parts['path'] ?? '/';
        $query = $parts['query'] ?? '';

        return $query === '' ? $path : $path.'?'.$query;
    }
}
