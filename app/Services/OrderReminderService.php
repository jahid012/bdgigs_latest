<?php

namespace App\Services;

use App\Events\OrderDeadlineReminder;
use App\Events\OrderOverdueAlert;
use App\Events\OrderRequirementsPendingReminder;
use App\Events\ReviewDeadlineReminder;
use App\Events\ReviewPeriodExpired;
use App\Models\Order;
use App\Models\OrderReminder;
use App\Models\User;
use Illuminate\Support\Carbon;

class OrderReminderService
{
    public function sendRequirementReminders(int $afterHours = 24): int
    {
        $sent = 0;

        Order::query()
            ->with(['buyer', 'seller'])
            ->where('payment_status', 'paid')
            ->whereIn('status', ['Waiting for Requirements', 'Pending Requirements'])
            ->where('created_at', '<=', now()->subHours($afterHours))
            ->chunkById(100, function ($orders) use (&$sent, $afterHours) {
                foreach ($orders as $order) {
                    if (! $order->buyer || $this->requirementsSubmitted($order)) {
                        continue;
                    }

                    $key = 'requirements_pending_24h';

                    if ($this->markSent($order, $key, $order->buyer, [
                        'after_hours' => $afterHours,
                    ])) {
                        event(new OrderRequirementsPendingReminder($order->fresh(['buyer', 'seller']), $key));
                        $sent++;
                    }
                }
            });

        return $sent;
    }

    public function sendDeadlineReminders(): int
    {
        $sent = 0;
        $now = now();

        Order::query()
            ->with(['buyer', 'seller'])
            ->whereNotNull('due_date')
            ->where('payment_status', 'paid')
            ->whereNull('cancelled_at')
            ->whereNotIn('status', ['Completed', 'Cancelled', 'Canceled', 'Payment Rejected'])
            ->chunkById(100, function ($orders) use (&$sent, $now) {
                foreach ($orders as $order) {
                    $deadline = $this->deadlineAt($order);

                    if ($deadline->isPast()) {
                        continue;
                    }

                    $hoursUntilDeadline = $now->diffInHours($deadline, false);
                    $key = null;
                    $label = null;

                    if ($hoursUntilDeadline <= 6) {
                        $key = 'deadline_6h';
                        $label = '6 hours';
                    } elseif ($hoursUntilDeadline <= 24) {
                        $key = 'deadline_24h';
                        $label = '24 hours';
                    }

                    if ($key && $this->markSent($order, $key, null, ['label' => $label])) {
                        event(new OrderDeadlineReminder($order->fresh(['buyer', 'seller']), $key, $label));
                        $sent++;
                    }
                }
            });

        return $sent;
    }

    public function markOverdue(): int
    {
        $marked = 0;

        Order::query()
            ->with(['buyer', 'seller'])
            ->whereNotNull('due_date')
            ->where('payment_status', 'paid')
            ->whereNull('overdue_at')
            ->whereNull('cancelled_at')
            ->whereNotIn('status', ['Completed', 'Cancelled', 'Canceled', 'Delivered', 'Payment Rejected'])
            ->chunkById(100, function ($orders) use (&$marked) {
                foreach ($orders as $order) {
                    if ($this->deadlineAt($order)->isFuture()) {
                        continue;
                    }

                    $order->forceFill([
                        'status' => 'Overdue',
                        'status_class' => 'status-cancelled',
                        'overdue_at' => now(),
                    ])->save();

                    if ($this->markSent($order, 'overdue_alert', null)) {
                        event(new OrderOverdueAlert($order->fresh(['buyer', 'seller'])));
                        $marked++;
                    }
                }
            });

        return $marked;
    }

    public function sendReviewReminders(): int
    {
        $sent = 0;

        Order::query()
            ->with(['buyer', 'seller', 'reviews'])
            ->where('status', 'Completed')
            ->whereNull('review_period_expired_at')
            ->chunkById(100, function ($orders) use (&$sent) {
                foreach ($orders as $order) {
                    $deadline = $this->reviewDeadlineFor($order);

                    if ($deadline->isPast() || $deadline->gt(now()->addDays(2))) {
                        continue;
                    }

                    $buyerReview = $order->reviews->firstWhere('role', 'buyer');
                    $sellerReview = $order->reviews->firstWhere('role', 'seller');

                    if (! $buyerReview && $order->buyer) {
                        $key = 'review_deadline_buyer';

                        if ($this->markSent($order, $key, $order->buyer)) {
                            event(new ReviewDeadlineReminder($order->fresh(['buyer', 'seller', 'reviews']), $order->buyer, $key));
                            $sent++;
                        }
                    } elseif ($buyerReview && ! $sellerReview && $order->seller) {
                        $key = 'review_deadline_seller';

                        if ($this->markSent($order, $key, $order->seller)) {
                            event(new ReviewDeadlineReminder($order->fresh(['buyer', 'seller', 'reviews']), $order->seller, $key));
                            $sent++;
                        }
                    }
                }
            });

        return $sent;
    }

    public function expireReviewPeriods(): int
    {
        $expired = 0;

        Order::query()
            ->with(['buyer', 'seller', 'reviews'])
            ->where('status', 'Completed')
            ->whereNull('review_period_expired_at')
            ->chunkById(100, function ($orders) use (&$expired) {
                foreach ($orders as $order) {
                    if ($this->reviewDeadlineFor($order)->isFuture()) {
                        continue;
                    }

                    $order->forceFill(['review_period_expired_at' => now()])->save();
                    event(new ReviewPeriodExpired($order->fresh(['buyer', 'seller', 'reviews'])));
                    $expired++;
                }
            });

        return $expired;
    }

    public function markSent(Order $order, string $key, ?User $recipient = null, array $metadata = []): bool
    {
        $reminder = OrderReminder::firstOrCreate(
            [
                'order_id' => $order->id,
                'key' => $key,
            ],
            [
                'recipient_id' => $recipient?->id,
                'sent_at' => now(),
                'metadata' => $metadata,
            ],
        );

        return $reminder->wasRecentlyCreated;
    }

    public function reviewDeadlineFor(Order $order): Carbon
    {
        return ($order->review_period_expires_at ?: ($order->updated_at ?: now())->copy()->addDays(OrderReviewService::DEADLINE_DAYS)->endOfDay())->copy();
    }

    private function requirementsSubmitted(Order $order): bool
    {
        if (! empty($order->metadata['requirementsSubmittedAt'])) {
            return true;
        }

        $items = collect($order->metadata['requirements'] ?? []);

        return $items->isNotEmpty()
            && $items
                ->filter(fn (array $item) => (bool) ($item['required'] ?? false))
                ->every(fn (array $item) => filled($item['answer'] ?? null) || count($item['files'] ?? []) > 0);
    }

    private function deadlineAt(Order $order): Carbon
    {
        return ($order->due_date ?: now())->copy()->endOfDay();
    }
}
