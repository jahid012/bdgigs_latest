<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'username', 'email', 'password', 'email_verified_at', 'profile_type', 'country', 'avatar', 'verification_status', 'suspended_at', 'deactivated_at', 'last_seen_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'suspended_at' => 'datetime',
            'deactivated_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if (! $user->username) {
                $user->username = static::uniqueUsername($user->name ?: Str::before($user->email, '@'));
            }
        });
    }

    public static function uniqueUsername(string $seed): string
    {
        $base = Str::slug($seed, '_') ?: 'user';
        $candidate = $base;
        $suffix = 1;

        while (static::where('username', $candidate)->exists()) {
            $suffix++;
            $candidate = "{$base}_{$suffix}";
        }

        return $candidate;
    }

    public function gigs(): HasMany
    {
        return $this->hasMany(Gig::class, 'seller_id');
    }

    public function buyerOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'buyer_id');
    }

    public function sellerOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'seller_id');
    }

    public function savedServices(): BelongsToMany
    {
        return $this->belongsToMany(Gig::class, 'saved_services', 'user_id', 'gig_id')->withTimestamps();
    }

    public function userNotifications(): HasMany
    {
        return $this->hasMany(UserNotification::class);
    }

    public function buyerProfile(): HasOne
    {
        return $this->hasOne(BuyerProfile::class);
    }

    public function sellerProfile(): HasOne
    {
        return $this->hasOne(SellerProfile::class);
    }

    public function billingProfile(): HasOne
    {
        return $this->hasOne(BillingProfile::class);
    }

    public function notificationPreference(): HasOne
    {
        return $this->hasOne(NotificationPreference::class);
    }

    public function identityVerificationSubmissions(): HasMany
    {
        return $this->hasMany(IdentityVerificationSubmission::class);
    }

    public function savedMessages(): BelongsToMany
    {
        return $this->belongsToMany(Message::class, 'message_saves')->withTimestamps();
    }

    public function conversationParticipants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(PushSubscription::class);
    }

    public function sellerPayoutMethods(): HasMany
    {
        return $this->hasMany(SellerPayoutMethod::class);
    }

    public function withdrawalRequests(): HasMany
    {
        return $this->hasMany(WithdrawalRequest::class, 'seller_id');
    }
}
