<?php

namespace App\Services;

use App\Models\Gig;
use App\Models\ManualPaymentMethod;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ManualOrderCheckoutService
{
    public function create(User $buyer, Gig $gig, array $payload): Order
    {
        abort_unless($gig->seller, 422, 'This gig does not have an available seller.');
        abort_if($gig->seller_id === $buyer->id, 422, 'You cannot order your own gig.');

        $method = ManualPaymentMethod::query()
            ->whereKey($payload['manualPaymentMethodId'])
            ->where('active', true)
            ->first();
        abort_unless($method, 422, 'Choose an available manual payment method.');

        $package = collect($gig->packages ?: [])
            ->first(fn (array $package) => ($package['id'] ?? null) === $payload['packageId']);
        abort_unless($package, 422, 'Choose an available package.');

        $priceCents = $this->moneyToCents($package['price'] ?? $gig->price_cents / 100);
        abort_unless($priceCents > 0, 422, 'This package does not have a valid price.');

        return DB::transaction(function () use ($buyer, $gig, $method, $package, $payload, $priceCents) {
            $order = Order::create([
                'code' => $this->nextOrderCode(),
                'buyer_id' => $buyer->id,
                'seller_id' => $gig->seller_id,
                'gig_id' => $gig->id,
                'service' => $gig->title,
                'buyer_name' => $buyer->name,
                'seller_name' => $gig->seller->name,
                'status' => 'Pending Payment Review',
                'status_class' => 'status-delivered',
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
                    'checkoutNote' => $payload['note'] ?? null,
                ],
            ]);

            $order->manualPaymentSubmission()->create([
                'manual_payment_method_id' => $method->id,
                'buyer_id' => $buyer->id,
                'amount_cents' => $priceCents,
                'currency' => 'USD',
                'reference' => trim($payload['reference']),
                'proof_reference' => $payload['proofReference'] ?? null,
                'status' => 'pending',
                'metadata' => [
                    'note' => $payload['note'] ?? null,
                ],
            ]);

            $order->activities()->create([
                'actor_id' => $buyer->id,
                'type' => 'checkout',
                'title' => 'Manual payment submitted',
                'detail' => 'The buyer created the order and submitted a payment reference for admin review.',
            ]);

            return $order->load(['buyer', 'seller', 'gig', 'activities', 'manualPaymentSubmission.method']);
        });
    }

    private function nextOrderCode(): string
    {
        do {
            $code = 'MO-'.Str::upper(Str::random(8));
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
