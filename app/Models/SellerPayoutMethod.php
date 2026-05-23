<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SellerPayoutMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'label',
        'account_holder',
        'account_number',
        'routing_details',
        'metadata',
        'active',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'active' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(WithdrawalRequest::class);
    }
}
