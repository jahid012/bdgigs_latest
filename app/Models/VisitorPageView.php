<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitorPageView extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'visitor_id',
        'session_id',
        'path',
        'page_title',
        'referrer',
        'user_agent',
        'ip_hash',
        'is_bot',
        'visited_at',
    ];

    protected function casts(): array
    {
        return [
            'is_bot' => 'boolean',
            'visited_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeHuman(Builder $query): Builder
    {
        return $query->where('is_bot', false);
    }
}
