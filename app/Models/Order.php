<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'buyer_id',
        'seller_id',
        'gig_id',
        'service',
        'buyer_name',
        'seller_name',
        'status',
        'status_class',
        'due_date',
        'price_cents',
        'earnings_cents',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'price_cents' => 'integer',
            'earnings_cents' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function gig(): BelongsTo
    {
        return $this->belongsTo(Gig::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(OrderActivity::class);
    }

    public function manualPaymentSubmission(): HasOne
    {
        return $this->hasOne(ManualPaymentSubmission::class);
    }
}
