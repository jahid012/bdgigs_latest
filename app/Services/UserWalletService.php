<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserWallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserWalletService
{
    public function wallet(User $user): UserWallet
    {
        return $user->wallet()->firstOrCreate([], [
            'balance_cents' => 0,
            'credits_cents' => 0,
            'refunded_cents' => 0,
            'currency' => 'USD',
        ]);
    }

    public function deposit(User $user, int $amountCents, string $method = 'manual_card', ?string $note = null): WalletTransaction
    {
        return DB::transaction(function () use ($user, $amountCents, $method, $note) {
            $wallet = $this->wallet($user);
            $wallet = UserWallet::whereKey($wallet->id)->lockForUpdate()->firstOrFail();

            $wallet->increment('balance_cents', $amountCents);

            return $wallet->transactions()->create([
                'user_id' => $user->id,
                'code' => $this->nextCode(),
                'type' => 'deposit',
                'amount_cents' => $amountCents,
                'currency' => $wallet->currency,
                'status' => 'completed',
                'method' => $method,
                'description' => 'Balance added to bdgigs wallet',
                'metadata' => ['note' => $note],
                'processed_at' => now(),
            ]);
        });
    }

    public function debit(User $user, int $amountCents, string $description, array $metadata = []): WalletTransaction
    {
        return DB::transaction(function () use ($user, $amountCents, $description, $metadata) {
            $wallet = UserWallet::whereKey($this->wallet($user)->id)->lockForUpdate()->firstOrFail();

            if ($wallet->balance_cents < $amountCents) {
                throw ValidationException::withMessages([
                    'balance' => 'Your wallet balance is not enough for this payment.',
                ]);
            }

            $wallet->decrement('balance_cents', $amountCents);

            return $wallet->transactions()->create([
                'user_id' => $user->id,
                'code' => $this->nextCode(),
                'type' => 'debit',
                'amount_cents' => -abs($amountCents),
                'currency' => $wallet->currency,
                'status' => 'completed',
                'method' => 'wallet_balance',
                'description' => $description,
                'metadata' => $metadata,
                'processed_at' => now(),
            ]);
        });
    }

    private function nextCode(): string
    {
        do {
            $code = 'WLT-'.Str::upper(Str::random(8));
        } while (WalletTransaction::where('code', $code)->exists());

        return $code;
    }
}
