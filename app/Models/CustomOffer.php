<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomOffer extends Model
{
    public const STATUSES = [
        'pending',
        'accepted',
        'paid',
        'declined',
        'expired',
        'cancelled',
        'payment_failed',
    ];

    protected $fillable = [
        'conversation_id',
        'seller_id',
        'buyer_id',
        'gig_id',
        'order_id',
        'code',
        'title',
        'description',
        'price_cents',
        'currency',
        'delivery_days',
        'revisions',
        'terms',
        'status',
        'expires_at',
        'accepted_at',
        'paid_at',
        'payment_failed_at',
        'declined_at',
        'cancelled_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'delivery_days' => 'integer',
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'paid_at' => 'datetime',
            'payment_failed_at' => 'datetime',
            'declined_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function gig(): BelongsTo
    {
        return $this->belongsTo(Gig::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function isPayable(): bool
    {
        return in_array($this->status, ['pending', 'accepted'], true)
            && (! $this->expires_at || $this->expires_at->isFuture());
    }
}
