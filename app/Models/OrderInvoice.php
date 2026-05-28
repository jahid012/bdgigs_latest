<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'buyer_id',
        'seller_id',
        'code',
        'currency',
        'amount_cents',
        'platform_fee_cents',
        'seller_earning_cents',
        'payment_method',
        'transaction_id',
        'issued_at',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'platform_fee_cents' => 'integer',
            'seller_earning_cents' => 'integer',
            'issued_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }
}
