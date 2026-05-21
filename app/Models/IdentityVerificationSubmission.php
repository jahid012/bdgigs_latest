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
    ];

    protected function casts(): array
    {
        return [
            'details' => 'array',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
