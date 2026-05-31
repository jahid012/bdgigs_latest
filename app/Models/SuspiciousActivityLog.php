<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuspiciousActivityLog extends Model
{
    use HasFactory;

    public const SEVERITIES = ['low', 'medium', 'high', 'critical'];

    protected $fillable = [
        'user_id',
        'type',
        'severity',
        'ip_address',
        'user_agent',
        'description',
        'metadata',
        'reviewed_at',
        'reviewed_by',
        'reviewed_by_admin_id',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function adminReviewer(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'reviewed_by_admin_id');
    }
}
