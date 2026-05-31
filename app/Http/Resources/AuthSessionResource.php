<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof User) {
            return [
                'authenticated' => false,
                'csrfToken' => csrf_token(),
            ];
        }

        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'country' => $this->country,
            'initials' => $this->initials($this->name),
            'online' => true,
            'role' => 'buyer',
            'sellerEnabled' => true,
            'sellerStatus' => $this->seller_status ?: 'not_applied',
            'twoFactorEnabled' => filled($this->two_factor_secret),
            'emailVerified' => $this->hasVerifiedEmail(),
            'emailVerifiedAt' => $this->email_verified_at?->toISOString(),
            'verificationStatus' => $this->verification_status,
            'accountStatus' => $this->suspended_at
                ? 'suspended'
                : ($this->deactivated_at ? 'deactivated' : 'active'),
            'authenticated' => true,
            'csrfToken' => csrf_token(),
        ];
    }

    private function initials(string $name): string
    {
        return collect(explode(' ', trim($name)))
            ->filter()
            ->map(fn (string $part) => mb_substr($part, 0, 1))
            ->take(2)
            ->implode('');
    }
}
