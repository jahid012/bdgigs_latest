<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WithdrawalRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'seller_id',
        'seller_payout_method_id',
        'amount_cents',
        'approved_amount_cents',
        'currency',
        'payout_snapshot',
        'status',
        'seller_note',
        'review_note',
        'payment_reference',
        'reviewed_by',
        'paid_by',
        'reviewed_at',
        'paid_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'approved_amount_cents' => 'integer',
            'payout_snapshot' => 'array',
            'reviewed_at' => 'datetime',
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function payoutMethod(): BelongsTo
    {
        return $this->belongsTo(SellerPayoutMethod::class, 'seller_payout_method_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(WithdrawalActivity::class);
    }
}
