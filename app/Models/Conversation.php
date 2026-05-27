<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_id',
        'created_by_id',
        'buyer_id',
        'seller_id',
        'gig_id',
        'context_type',
        'context_id',
        'subject',
        'buyer_name',
        'seller_name',
        'status',
        'status_class',
        'priority',
        'buyer_unread_count',
        'seller_unread_count',
        'last_message_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'buyer_unread_count' => 'integer',
            'seller_unread_count' => 'integer',
            'last_message_at' => 'datetime',
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function gig(): BelongsTo
    {
        return $this->belongsTo(Gig::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->oldest('sent_at')->oldest();
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    public function disputes(): HasMany
    {
        return $this->hasMany(Dispute::class);
    }

    public function customOffers(): HasMany
    {
        return $this->hasMany(CustomOffer::class);
    }

    public function participantUsers(): HasManyThrough
    {
        return $this->hasManyThrough(
            User::class,
            ConversationParticipant::class,
            'conversation_id',
            'id',
            'id',
            'user_id',
        );
    }

    public function participantFor(User|int $user): ?ConversationParticipant
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $this->participants->firstWhere('user_id', $userId)
            ?: $this->participants()->where('user_id', $userId)->first();
    }
}
