<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdentityVerificationSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        'details',
        'document_path',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
        'reviewed_by_admin_id',
        'review_note',
        'additional_document_requested_at',
        'additional_document_note',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'details' => 'array',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'additional_document_requested_at' => 'datetime',
            'metadata' => 'array',
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
