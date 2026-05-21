<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'user_id',
        'context_role',
        'unread_count',
        'last_read_at',
        'last_seen_at',
        'last_typing_at',
        'archived_at',
        'muted_at',
        'last_email_reminded_at',
    ];

    protected function casts(): array
    {
        return [
            'unread_count' => 'integer',
            'last_read_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'last_typing_at' => 'datetime',
            'archived_at' => 'datetime',
            'muted_at' => 'datetime',
            'last_email_reminded_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
