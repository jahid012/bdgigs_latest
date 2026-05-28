<?php

namespace App\Services;

use App\Events\AccountDeactivated;
use App\Events\AccountReactivated;
use App\Events\AccountSuspended;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AccountStatusService
{
    public function suspend(User $user, ?User $actor, ?string $reason = null): User
    {
        return DB::transaction(function () use ($user, $actor, $reason) {
            $previousSellerStatus = $user->seller_status;
            $user->forceFill([
                'verification_status' => 'suspended',
                'seller_status' => $user->seller_status === 'not_applied' ? 'not_applied' : 'suspended',
                'suspended_at' => now(),
                'suspension_reason' => $reason,
                'suspended_by' => $actor?->id,
            ])->save();

            $this->record($user, $actor, 'account_suspended', 'suspended', $reason);
            $this->recordSellerStatus($user, $actor, $previousSellerStatus, $user->seller_status, $reason);
            $this->destroyUserSessions($user);

            DB::afterCommit(fn () => event(new AccountSuspended($user->fresh(), $actor, $reason)));

            return $user->fresh();
        });
    }

    public function reactivate(User $user, ?User $actor, ?string $reason = null): User
    {
        return DB::transaction(function () use ($user, $actor, $reason) {
            $previousSellerStatus = $user->seller_status;
            $user->forceFill([
                'verification_status' => $user->email_verified_at ? 'verified' : 'active',
                'seller_status' => $user->seller_status === 'suspended' ? 'approved' : $user->seller_status,
                'suspended_at' => null,
                'suspension_reason' => null,
                'suspended_by' => null,
                'deactivated_at' => null,
                'deactivation_reason' => null,
                'deactivated_by' => null,
                'reactivated_at' => now(),
            ])->save();

            $this->record($user, $actor, 'account_reactivated', 'active', $reason);
            $this->recordSellerStatus($user, $actor, $previousSellerStatus, $user->seller_status, $reason);

            DB::afterCommit(fn () => event(new AccountReactivated($user->fresh(), $actor, $reason)));

            return $user->fresh();
        });
    }

    public function deactivate(User $user, ?User $actor, ?string $reason = null): User
    {
        return DB::transaction(function () use ($user, $actor, $reason) {
            $user->forceFill([
                'verification_status' => 'deactivated',
                'deactivated_at' => now(),
                'deactivation_reason' => $reason,
                'deactivated_by' => $actor?->id,
            ])->save();

            $this->record($user, $actor, 'account_deactivated', 'deactivated', $reason);
            $this->destroyUserSessions($user);

            DB::afterCommit(fn () => event(new AccountDeactivated($user->fresh(), $actor, $reason)));

            return $user->fresh();
        });
    }

    private function record(User $user, ?User $actor, string $eventType, string $status, ?string $reason): void
    {
        $user->accountStatusEvents()->create([
            'actor_id' => $actor?->id,
            'event_type' => $eventType,
            'status' => $status,
            'reason' => $reason,
            'metadata' => [
                'actor_name' => $actor?->name,
            ],
        ]);
    }

    private function recordSellerStatus(User $user, ?User $actor, ?string $from, ?string $to, ?string $reason): void
    {
        if ($from === $to || ! $to) {
            return;
        }

        $user->sellerStatusEvents()->create([
            'actor_id' => $actor?->id,
            'from_status' => $from,
            'to_status' => $to,
            'reason' => $reason,
        ]);
    }

    private function destroyUserSessions(User $user): void
    {
        DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->id)
            ->delete();
    }
}
