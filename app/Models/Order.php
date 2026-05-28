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
        'payment_status',
        'paid_at',
        'payment_method',
        'transaction_id',
        'refunded_at',
        'refund_amount_cents',
        'due_date',
        'work_started_at',
        'overdue_at',
        'cancelled_at',
        'cancellation_status',
        'refund_status',
        'review_period_expires_at',
        'review_period_expired_at',
        'reviews_visible_at',
        'price_cents',
        'earnings_cents',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'paid_at' => 'datetime',
            'refunded_at' => 'datetime',
            'work_started_at' => 'datetime',
            'overdue_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'review_period_expires_at' => 'datetime',
            'review_period_expired_at' => 'datetime',
            'reviews_visible_at' => 'datetime',
            'price_cents' => 'integer',
            'earnings_cents' => 'integer',
            'refund_amount_cents' => 'integer',
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

    public function timeExtensionRequests(): HasMany
    {
        return $this->hasMany(OrderTimeExtensionRequest::class);
    }

    public function privateNotes(): HasMany
    {
        return $this->hasMany(OrderPrivateNote::class);
    }

    public function manualPaymentSubmission(): HasOne
    {
        return $this->hasOne(ManualPaymentSubmission::class);
    }

    public function disputes(): HasMany
    {
        return $this->hasMany(Dispute::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(OrderReview::class);
    }

    public function customOffer(): HasOne
    {
        return $this->hasOne(CustomOffer::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(OrderInvoice::class);
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(OrderReminder::class);
    }

    public function cancellations(): HasMany
    {
        return $this->hasMany(OrderCancellation::class);
    }

    public function latestCancellation(): HasOne
    {
        return $this->hasOne(OrderCancellation::class)->latestOfMany();
    }
}
