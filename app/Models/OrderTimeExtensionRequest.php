<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderTimeExtensionRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'requested_by_id',
        'reviewed_by_id',
        'days_requested',
        'original_due_date',
        'requested_due_date',
        'reason',
        'status',
        'decided_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'days_requested' => 'integer',
            'original_due_date' => 'date',
            'requested_due_date' => 'date',
            'decided_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }
}
