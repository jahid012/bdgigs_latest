<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ModerationReport extends Model
{
    use HasFactory;

    public const TYPES = ['user', 'gig', 'order', 'message'];
    public const STATUSES = ['pending', 'reviewing', 'resolved', 'rejected'];

    protected $fillable = [
        'code',
        'reporter_id',
        'reported_user_id',
        'reportable_type',
        'reportable_id',
        'type',
        'status',
        'reason',
        'description',
        'assigned_to_id',
        'resolved_by_id',
        'resolution_note',
        'resolved_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reportedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_user_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_id');
    }

    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }
}
