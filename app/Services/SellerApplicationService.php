<?php

namespace App\Services;

use App\Events\SellerApplicationApproved;
use App\Events\SellerApplicationRejected;
use App\Events\SellerApplicationSubmitted;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SellerApplicationService
{
    public function submit(User $seller, array $payload = []): User
    {
        if (in_array($seller->seller_status, ['pending', 'approved', 'suspended'], true)) {
            throw ValidationException::withMessages([
                'seller' => 'Your seller application is already active or under review.',
            ]);
        }

        return DB::transaction(function () use ($seller, $payload) {
            $previous = $seller->seller_status ?: 'not_applied';

            $seller->forceFill([
                'profile_type' => 'seller',
                'seller_status' => 'pending',
                'seller_status_reason' => $payload['reason'] ?? null,
                'seller_status_reviewed_by' => null,
                'seller_status_reviewed_at' => null,
            ])->save();

            $seller->sellerStatusEvents()->create([
                'from_status' => $previous,
                'to_status' => 'pending',
                'reason' => $payload['reason'] ?? 'Seller applied for review.',
                'metadata' => ['source' => $payload['source'] ?? 'dashboard'],
            ]);

            DB::afterCommit(fn () => event(new SellerApplicationSubmitted($seller->fresh(['sellerProfile']))));

            return $seller->fresh(['sellerProfile']);
        });
    }

    public function approve(User $seller, ?User $admin, ?string $reason = null): User
    {
        return $this->transition($seller, $admin, 'approved', $reason ?: 'Seller application approved.');
    }

    public function reject(User $seller, ?User $admin, string $reason): User
    {
        return $this->transition($seller, $admin, 'rejected', $reason);
    }

    private function transition(User $seller, ?User $admin, string $status, string $reason): User
    {
        return DB::transaction(function () use ($seller, $admin, $status, $reason) {
            $previous = $seller->seller_status ?: 'not_applied';

            $seller->forceFill([
                'profile_type' => 'seller',
                'seller_status' => $status,
                'seller_status_reason' => $reason,
                'seller_status_reviewed_by' => $admin?->id,
                'seller_status_reviewed_at' => now(),
            ])->save();

            $seller->sellerStatusEvents()->create([
                'actor_id' => $admin?->id,
                'from_status' => $previous,
                'to_status' => $status,
                'reason' => $reason,
            ]);

            DB::afterCommit(function () use ($seller, $admin, $status, $reason) {
                $status === 'approved'
                    ? event(new SellerApplicationApproved($seller->fresh(['sellerProfile']), $admin))
                    : event(new SellerApplicationRejected($seller->fresh(['sellerProfile']), $admin, $reason));
            });

            return $seller->fresh(['sellerProfile']);
        });
    }
}
