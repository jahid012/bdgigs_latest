<?php

namespace App\Services;

use App\Events\OrderPaymentFailed;
use App\Events\OrderPaymentSuccessful;
use App\Events\OrderPlaced;
use App\Events\OrderRefunded;
use App\Models\Admin;
use App\Models\Gig;
use App\Models\Order;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderPaymentLifecycleService
{
    public function __construct(
        private readonly UserWalletService $wallets,
        private readonly OrderInvoiceService $invoices,
    ) {
    }

    public function createWalletOrder(User $buyer, Gig $gig, array $payload): Order
    {
        $this->ensureVerifiedBuyer($buyer);
        abort_unless($gig->seller, 422, 'This gig does not have an available seller.');
        abort_if($gig->seller_id === $buyer->id, 422, 'You cannot order your own gig.');

        $package = collect($gig->packages ?: [])
            ->first(fn (array $package) => ($package['id'] ?? null) === $payload['packageId']);
        abort_unless($package, 422, 'Choose an available package.');

        $priceCents = $this->moneyToCents($package['price'] ?? $gig->price_cents / 100);
        abort_unless($priceCents > 0, 422, 'This package does not have a valid price.');

        return DB::transaction(function () use ($buyer, $gig, $package, $payload, $priceCents) {
            $order = $this->createBaseOrder($buyer, $gig, $package, $payload, $priceCents, [
                'checkoutNote' => $payload['note'] ?? null,
                'payment' => ['method' => 'wallet_balance'],
            ]);

            $transaction = $this->wallets->debit($buyer, $priceCents, 'Order payment '.$order->code, [
                'order_id' => $order->id,
                'order_code' => $order->code,
            ]);

            $metadata = $order->metadata ?: [];
            $metadata['payment']['buyer_transaction_id'] = $transaction->code;
            $order->forceFill(['metadata' => $metadata])->save();

            DB::afterCommit(fn () => event(new OrderPlaced($order->fresh(['buyer', 'seller', 'gig']))));

            return $this->markSuccessful($order, 'wallet_balance', $transaction->code, $buyer, $transaction);
        });
    }

    public function markSuccessful(
        Order $order,
        string $method = 'manual_payment',
        ?string $transactionId = null,
        User|Admin|null $actor = null,
        ?WalletTransaction $buyerTransaction = null
    ): Order {
        return DB::transaction(function () use ($order, $method, $transactionId, $actor, $buyerTransaction) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->payment_status === 'paid') {
                return $order->fresh(['buyer', 'seller', 'gig', 'invoice']);
            }

            $metadata = $order->metadata ?: [];
            $payment = $metadata['payment'] ?? [];

            if (! $buyerTransaction && $order->buyer) {
                $buyerTransaction = $this->wallets->record(
                    $order->buyer,
                    'order_payment',
                    -abs((int) $order->price_cents),
                    'completed',
                    $method,
                    'Order payment '.$order->code,
                    ['order_id' => $order->id, 'order_code' => $order->code],
                );
            }

            if ($order->seller) {
                $this->wallets->record(
                    $order->seller,
                    'pending_earning',
                    abs((int) $order->earnings_cents),
                    'pending',
                    'order_escrow',
                    'Pending earning for order '.$order->code,
                    ['order_id' => $order->id, 'order_code' => $order->code],
                );
            }

            $payment['method'] = $method;
            $payment['status'] = 'paid';
            $payment['paid_at'] = now()->toISOString();
            $payment['buyer_transaction_id'] = $buyerTransaction?->code ?: $transactionId;
            $metadata['payment'] = $payment;

            $order->forceFill([
                'status' => $this->hasRequirements($order) ? 'Waiting for Requirements' : 'In Progress',
                'status_class' => $this->hasRequirements($order) ? 'status-delivered' : 'status-progress',
                'payment_status' => 'paid',
                'paid_at' => now(),
                'payment_method' => $method,
                'transaction_id' => $buyerTransaction?->code ?: $transactionId,
                'metadata' => $metadata,
            ])->save();

            $this->invoices->generate($order->fresh(['buyer', 'seller', 'gig']));

            DB::afterCommit(fn () => event(new OrderPaymentSuccessful($order->fresh(['buyer', 'seller', 'gig', 'invoice']))));

            return $order->fresh(['buyer', 'seller', 'gig', 'invoice']);
        });
    }

    public function markFailed(Order $order, string $reason, string $method = 'manual_payment', User|Admin|null $actor = null): Order
    {
        return DB::transaction(function () use ($order, $reason, $method) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $metadata = $order->metadata ?: [];
            $metadata['payment'] = [
                ...($metadata['payment'] ?? []),
                'method' => $method,
                'status' => 'failed',
                'failed_at' => now()->toISOString(),
                'failure_reason' => $reason,
            ];

            if ($order->buyer) {
                $transaction = $this->wallets->record(
                    $order->buyer,
                    'order_payment',
                    -abs((int) $order->price_cents),
                    'failed',
                    $method,
                    'Failed order payment '.$order->code,
                    ['order_id' => $order->id, 'order_code' => $order->code, 'reason' => $reason],
                );
                $metadata['payment']['buyer_transaction_id'] = $transaction->code;
            }

            $order->forceFill([
                'status' => 'Payment Rejected',
                'status_class' => 'status-progress',
                'payment_status' => 'failed',
                'payment_method' => $method,
                'metadata' => $metadata,
            ])->save();

            DB::afterCommit(fn () => event(new OrderPaymentFailed($order->fresh(['buyer', 'seller', 'gig']), $reason)));

            return $order->fresh(['buyer', 'seller', 'gig']);
        });
    }

    public function refund(Order $order, User|Admin|null $actor, ?int $amountCents = null, ?string $reason = null): Order
    {
        return DB::transaction(function () use ($order, $actor, $amountCents, $reason) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->payment_status !== 'paid') {
                throw ValidationException::withMessages([
                    'order' => 'Only paid orders can be refunded.',
                ]);
            }

            if (! $order->buyer) {
                throw ValidationException::withMessages([
                    'buyer' => 'This order does not have a buyer to refund.',
                ]);
            }

            $remainingCents = max(0, (int) $order->price_cents - (int) $order->refund_amount_cents);
            $refundCents = $amountCents ?: $remainingCents;

            if ($refundCents <= 0 || $refundCents > $remainingCents) {
                throw ValidationException::withMessages([
                    'amount' => 'Refund amount must be within the remaining paid amount.',
                ]);
            }

            $transaction = $this->wallets->refund($order->buyer, $refundCents, 'Refund for order '.$order->code, [
                'order_id' => $order->id,
                'order_code' => $order->code,
                'reason' => $reason,
            ]);

            if ($order->seller) {
                $this->wallets->record(
                    $order->seller,
                    'earning_reversal',
                    -abs(min($refundCents, (int) $order->earnings_cents)),
                    'completed',
                    'order_refund',
                    'Earning reversal for order '.$order->code,
                    ['order_id' => $order->id, 'order_code' => $order->code],
                );
            }

            $metadata = $order->metadata ?: [];
            $metadata['refunds'][] = [
                'transaction_id' => $transaction->code,
                'amount_cents' => $refundCents,
                'reason' => $reason,
                'actor_id' => $actor instanceof User ? $actor->id : null,
                'actor_admin_id' => $actor instanceof Admin ? $actor->id : null,
                'refunded_at' => now()->toISOString(),
            ];

            $order->forceFill([
                'status' => 'Cancelled',
                'status_class' => 'status-cancelled',
                'payment_status' => ($refundCents === $remainingCents) ? 'refunded' : 'partially_refunded',
                'refunded_at' => now(),
                'cancelled_at' => now(),
                'refund_status' => 'processed',
                'refund_amount_cents' => (int) $order->refund_amount_cents + $refundCents,
                'metadata' => $metadata,
            ])->save();

            DB::afterCommit(fn () => event(new OrderRefunded(
                $order->fresh(['buyer', 'seller', 'gig']),
                $transaction,
                $refundCents,
                $actor,
                $reason,
            )));

            return $order->fresh(['buyer', 'seller', 'gig', 'invoice']);
        });
    }

    public function ensureVerifiedBuyer(User $buyer): void
    {
        if (! $buyer->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'email' => 'Verify your email before placing marketplace orders.',
            ]);
        }
    }

    private function createBaseOrder(User $buyer, Gig $gig, array $package, array $payload, int $priceCents, array $metadata = []): Order
    {
        return Order::create([
            'code' => $this->nextOrderCode('WO'),
            'buyer_id' => $buyer->id,
            'seller_id' => $gig->seller_id,
            'gig_id' => $gig->id,
            'service' => $gig->title,
            'buyer_name' => $buyer->name,
            'seller_name' => $gig->seller->name,
            'status' => 'Pending Payment',
            'status_class' => 'status-delivered',
            'payment_status' => 'pending',
            'due_date' => now()->addDays($this->deliveryDays($package, $gig))->toDateString(),
            'price_cents' => $priceCents,
            'earnings_cents' => (int) round($priceCents * 0.85),
            'metadata' => [
                'itemSummary' => $package['title'] ?? $package['name'] ?? 'Gig package',
                'packageId' => $package['id'] ?? null,
                'packageName' => $package['name'] ?? $package['label'] ?? null,
                'quantity' => 1,
                'duration' => $package['delivery'] ?? null,
                'revisions' => $package['revisions'] ?? null,
                'requirements' => collect($gig->requirements ?: [])
                    ->map(fn (array $requirement) => [
                        'label' => $requirement['label'] ?? 'Requirement',
                        'answer' => '',
                        'required' => (bool) ($requirement['required'] ?? false),
                    ])
                    ->values()
                    ->all(),
                ...$metadata,
            ],
        ]);
    }

    private function hasRequirements(Order $order): bool
    {
        return collect($order->metadata['requirements'] ?? [])->isNotEmpty();
    }

    private function nextOrderCode(string $prefix): string
    {
        do {
            $code = $prefix.'-'.Str::upper(Str::random(8));
        } while (Order::where('code', $code)->exists());

        return $code;
    }

    private function moneyToCents(string|int|float $value): int
    {
        return (int) round((float) preg_replace('/[^0-9.]/', '', (string) $value) * 100);
    }

    private function deliveryDays(array $package, Gig $gig): int
    {
        preg_match('/(\d+)/', (string) ($package['delivery'] ?? ''), $matches);

        return max(1, (int) ($matches[1] ?? $gig->delivery_days ?: 3));
    }
}
