<?php

namespace App\Services;

use App\Events\ReviewsVisible;
use App\Models\Gig;
use App\Models\Order;
use App\Models\OrderReview;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderReviewService
{
    public const DEADLINE_DAYS = 15;

    public function __construct(private readonly OrderEventNotificationService $events)
    {
    }

    public function submit(Order $order, User $reviewer, array $payload): OrderReview
    {
        $role = $this->participantRole($order, $reviewer);
        $this->ensureReviewable($order, $reviewer, $role);

        return DB::transaction(function () use ($order, $reviewer, $payload, $role) {
            $review = $order->reviews()->create([
                'reviewer_id' => $reviewer->id,
                'reviewee_id' => $role === 'buyer' ? $order->seller_id : $order->buyer_id,
                'role' => $role,
                'rating' => (int) $payload['rating'],
                'comment' => $payload['comment'],
                'submitted_at' => now(),
            ]);

            $order->activities()->create([
                'actor_id' => $reviewer->id,
                'type' => 'review_submitted',
                'title' => str($role)->title()->toString().' review submitted',
                'detail' => $role === 'buyer'
                    ? 'The buyer submitted a private review for the seller.'
                    : 'The seller submitted their review, so mutual reviews are now visible.',
            ]);

            if ($role === 'buyer' && $order->gig) {
                $this->applyGigRating($order->gig, (int) $payload['rating']);
            }

            if ($role === 'seller') {
                $order->forceFill(['reviews_visible_at' => $order->reviews_visible_at ?: now()])->save();
                DB::afterCommit(fn () => event(new ReviewsVisible($order->fresh(['buyer', 'seller', 'reviews']))));
            }

            $recipient = $role === 'buyer' ? $order->seller : null;

            if ($recipient) {
                $this->events->send(
                    $recipient,
                    'order_review_submitted',
                    'Buyer review submitted',
                    'The buyer submitted a review for order #'.$order->code.'. Submit your review to unlock both reviews.',
                    '/dashboard/seller/orders/'.$order->code,
                    [
                        'orderId' => $order->code,
                        'emailTemplate' => 'seller_review_request',
                        'review_deadline' => $this->deadlineFor($order)->format('M j, Y'),
                    ],
                );
            }

            return $review->fresh(['reviewer', 'reviewee']);
        });
    }

    public function deadlineFor(Order $order): Carbon
    {
        if ($order->review_period_expires_at) {
            return $order->review_period_expires_at->copy();
        }

        return $this->completedAt($order)->copy()->addDays(self::DEADLINE_DAYS)->endOfDay();
    }

    private function ensureReviewable(Order $order, User $reviewer, string $role): void
    {
        if (strtolower($order->status) !== 'completed') {
            throw ValidationException::withMessages([
                'rating' => 'Reviews can only be submitted after the order is completed.',
            ]);
        }

        if (now()->gt($this->deadlineFor($order))) {
            throw ValidationException::withMessages([
                'rating' => 'The 15 day review deadline has passed.',
            ]);
        }

        if ($order->reviews()->where('reviewer_id', $reviewer->id)->exists()) {
            throw ValidationException::withMessages([
                'rating' => 'You already submitted a review for this order.',
            ]);
        }

        if ($role === 'seller' && ! $order->reviews()->where('role', 'buyer')->exists()) {
            throw ValidationException::withMessages([
                'rating' => 'The buyer must submit a review before the seller can review this order.',
            ]);
        }
    }

    private function participantRole(Order $order, User $reviewer): string
    {
        if ((int) $order->buyer_id === (int) $reviewer->id) {
            return 'buyer';
        }

        if ((int) $order->seller_id === (int) $reviewer->id) {
            return 'seller';
        }

        throw new AuthorizationException('You cannot review this order.');
    }

    private function completedAt(Order $order): Carbon
    {
        $activity = $order->relationLoaded('activities')
            ? $order->activities
                ->filter(fn ($activity) => str_contains(strtolower((string) $activity->detail), 'to completed'))
                ->sortByDesc('created_at')
                ->first()
            : null;

        return ($activity?->created_at ?: $order->updated_at ?: $order->created_at ?: now())->copy();
    }

    private function applyGigRating(Gig $gig, int $rating): void
    {
        $currentCount = (int) $gig->reviews;
        $currentRating = (float) $gig->rating;
        $nextCount = $currentCount + 1;
        $nextRating = (($currentRating * $currentCount) + $rating) / max(1, $nextCount);

        $gig->forceFill([
            'reviews' => $nextCount,
            'rating' => round($nextRating, 1),
        ])->save();
    }
}
