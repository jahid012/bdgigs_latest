<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLoginDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'device_hash',
        'ip_address',
        'user_agent',
        'browser',
        'device',
        'location',
        'first_seen_at',
        'last_seen_at',
        'last_alerted_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'last_alerted_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
