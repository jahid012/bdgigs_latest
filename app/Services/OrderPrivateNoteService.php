<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderPrivateNote;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

class OrderPrivateNoteService
{
    public function create(Order $order, User $user, array $payload): OrderPrivateNote
    {
        $this->authorizeOrderAccess($order, $user);

        return $order->privateNotes()->create([
            'user_id' => $user->id,
            'body' => trim($payload['body']),
        ]);
    }

    public function update(Order $order, OrderPrivateNote $note, User $user, array $payload): OrderPrivateNote
    {
        $this->authorizeNoteOwnership($order, $note, $user);

        $note->forceFill([
            'body' => trim($payload['body']),
        ])->save();

        return $note->refresh();
    }

    public function delete(Order $order, OrderPrivateNote $note, User $user): void
    {
        $this->authorizeNoteOwnership($order, $note, $user);

        $note->delete();
    }

    private function authorizeOrderAccess(Order $order, User $user): void
    {
        $isParticipant = in_array((int) $user->id, [
            (int) $order->buyer_id,
            (int) $order->seller_id,
        ], true);

        if (! $isParticipant && ! $user->can('orders.view') && ! $user->can('orders.manage')) {
            throw new AuthorizationException('You cannot manage notes for this order.');
        }
    }

    private function authorizeNoteOwnership(Order $order, OrderPrivateNote $note, User $user): void
    {
        if ((int) $note->order_id !== (int) $order->id) {
            throw ValidationException::withMessages([
                'note' => 'This note does not belong to the order.',
            ]);
        }

        if ((int) $note->user_id !== (int) $user->id) {
            throw new AuthorizationException('You can only manage your own private notes.');
        }
    }
}
