<?php

namespace App\Services;

use App\Events\OrderInvoiceGenerated;
use App\Models\Order;
use App\Models\OrderInvoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderInvoiceService
{
    public function generate(Order $order): OrderInvoice
    {
        $order->loadMissing(['buyer', 'seller', 'gig']);
        $platformFee = max(0, (int) $order->price_cents - (int) $order->earnings_cents);
        $payload = [
            'order_id' => $order->code,
            'buyer_name' => $order->buyer?->name ?: $order->buyer_name,
            'seller_name' => $order->seller?->name ?: $order->seller_name,
            'gig_title' => $order->gig?->title ?: $order->service,
            'amount' => $this->money((int) $order->price_cents),
            'platform_fee' => $this->money($platformFee),
            'seller_earning' => $this->money((int) $order->earnings_cents),
            'payment_method' => $order->payment_method ?: 'manual',
            'transaction_id' => $order->transaction_id,
            'date' => now()->format('M j, Y'),
            'platform_name' => config('app.name', 'BDGigs'),
        ];

        $invoice = DB::transaction(function () use ($order, $platformFee, $payload) {
            return OrderInvoice::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'buyer_id' => $order->buyer_id,
                    'seller_id' => $order->seller_id,
                    'code' => $order->invoice?->code ?: $this->nextCode(),
                    'currency' => 'USD',
                    'amount_cents' => (int) $order->price_cents,
                    'platform_fee_cents' => $platformFee,
                    'seller_earning_cents' => (int) $order->earnings_cents,
                    'payment_method' => $order->payment_method,
                    'transaction_id' => $order->transaction_id,
                    'issued_at' => now(),
                    'payload' => $payload,
                ],
            );
        });

        DB::afterCommit(fn () => event(new OrderInvoiceGenerated($invoice->fresh(['order.buyer', 'order.seller']))));

        return $invoice;
    }

    public function payload(OrderInvoice $invoice): array
    {
        return [
            'invoiceId' => $invoice->code,
            'issuedAt' => $invoice->issued_at?->format('M j, Y g:i A'),
            ...($invoice->payload ?: []),
        ];
    }

    private function nextCode(): string
    {
        do {
            $code = 'INV-'.Str::upper(Str::random(8));
        } while (OrderInvoice::where('code', $code)->exists());

        return $code;
    }

    private function money(int $cents): string
    {
        return '$'.number_format($cents / 100, 2);
    }
}
