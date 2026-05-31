<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dispute extends Model
{
    use HasFactory;

    public const STATUSES = [
        'open',
        'awaiting_response',
        'evidence_requested',
        'under_admin_review',
        'resolved',
        'rejected',
        'closed',
    ];

    public const PRIORITIES = [
        'normal',
        'high',
        'critical',
    ];

    protected $fillable = [
        'order_id',
        'conversation_id',
        'opened_by_id',
        'opened_by_admin_id',
        'assigned_to_id',
        'assigned_to_admin_id',
        'resolved_by_id',
        'resolved_by_admin_id',
        'case_code',
        'reason',
        'description',
        'priority',
        'status',
        'resolution',
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
        return 'case_code';
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by_id');
    }

    public function openedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'opened_by_admin_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assigned_to_admin_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_id');
    }

    public function resolvedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'resolved_by_admin_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(DisputeActivity::class);
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, ['resolved', 'rejected', 'closed'], true);
    }
}
