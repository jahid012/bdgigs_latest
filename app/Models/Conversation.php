<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_id',
        'buyer_id',
        'seller_id',
        'gig_id',
        'subject',
        'buyer_name',
        'seller_name',
        'status',
        'status_class',
        'priority',
        'buyer_unread_count',
        'seller_unread_count',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'buyer_unread_count' => 'integer',
            'seller_unread_count' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
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

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->oldest('sent_at')->oldest();
    }
}
