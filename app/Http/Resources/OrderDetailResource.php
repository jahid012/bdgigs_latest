<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class OrderDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isSeller = $request->query('role') === 'seller';
        $counterparty = $isSeller ? $this->buyer : $this->seller;
        $counterpartyName = $isSeller ? $this->buyer_name : $this->seller_name;
        $timeExtensions = $this->relationLoaded('timeExtensionRequests')
            ? $this->timeExtensionRequests
            : collect();
        $privateNotes = $this->relationLoaded('privateNotes')
            ? $this->privateNotes
            : collect();
        $disputes = $this->relationLoaded('disputes')
            ? $this->disputes
            : collect();
        $reviews = $this->relationLoaded('reviews')
            ? $this->reviews
            : collect();
        $latestCancellation = $this->relationLoaded('latestCancellation')
            ? $this->latestCancellation
            : null;

        return [
            'id' => '#'.$this->code,
            'orderNumber' => $this->code,
            'serviceTitle' => $this->service,
            'serviceSummary' => $this->gig?->title ?: $this->service,
            'serviceImage' => $this->assetPath($this->gig?->image),
            'orderedBy' => $this->buyer_name ?: 'Buyer',
            'dateOrdered' => $this->created_at?->format('M j, Y, g:i A'),
            'deliveryDate' => $this->due_date?->format('M j, Y'),
            'deliveryDueAt' => $this->due_date?->copy()->endOfDay()->toISOString(),
            'totalPrice' => '$'.number_format($this->price_cents / 100, 0),
            'earnings' => '$'.number_format($this->earnings_cents / 100, 0),
            'status' => $this->status,
            'statusKey' => str($this->status)->lower()->replace([' ', '-'], '_')->toString(),
            'statusClass' => $this->status_class,
            'overdue' => (bool) $this->overdue_at,
            'overdueAt' => $this->overdue_at?->format('M j, Y g:i A'),
            'paymentStatus' => $this->payment_status,
            'paymentMethod' => $this->payment_method,
            'paidAt' => $this->paid_at?->format('M j, Y g:i A'),
            'transactionId' => $this->transaction_id,
            'refundAmount' => '$'.number_format(((int) $this->refund_amount_cents) / 100, 2),
            'receipt' => $this->invoice ? [
                'id' => $this->invoice->code,
                'issuedAt' => $this->invoice->issued_at?->format('M j, Y g:i A'),
                'url' => '/api/orders/'.$this->code.'/receipt',
            ] : null,
            'counterpartyName' => $counterpartyName ?: 'Member',
            'counterpartyHandle' => $counterparty?->username ? '@'.$counterparty->username : '',
            'counterpartyInitials' => initialsFromOrderName($counterpartyName ?: 'Member'),
            'counterpartyAvatar' => $this->assetPath($counterparty?->avatar),
            'counterpartyOnline' => $counterparty?->last_seen_at?->greaterThan(now()->subSeconds(90)) ?? false,
            'buyerAvatar' => $this->assetPath($this->buyer?->avatar),
            'sellerAvatar' => $this->assetPath($this->seller?->avatar),
            'itemSummary' => $this->metadata['itemSummary'] ?? 'Marketplace order',
            'quantity' => (string) ($this->metadata['quantity'] ?? 1),
            'duration' => $this->metadata['duration'] ?? ($this->gig?->delivery_days ? $this->gig->delivery_days.' days' : ''),
            'revisions' => $this->metadata['revisions'] ?? '',
            'requirements' => $this->requirementsPayload(),
            'requirementsState' => $this->requirementsState($request),
            'deliveryFlow' => $this->deliveryFlowPayload($request),
            'activity' => $this->activityPayload(),
            'timeExtension' => $this->timeExtensionPayload($request, $timeExtensions),
            'cancellation' => $this->cancellationPayload($request, $latestCancellation),
            'faq' => $this->faqPayload($request),
            'resolutionCenter' => $this->resolutionPayload($request, $disputes),
            'reviewsState' => $this->reviewsPayload($request, $reviews),
            'privateNotes' => $privateNotes
                ->map(fn ($note) => [
                    'id' => $note->id,
                    'body' => $note->body,
                    'createdAt' => $note->created_at?->format('M j, Y g:i A'),
                    'updatedAt' => $note->updated_at?->format('M j, Y g:i A'),
                ])
                ->values()
                ->all(),
            'paymentReviewStatus' => $this->manualPaymentSubmission?->status,
        ];
    }

    private function deliveryFlowPayload(Request $request): array
    {
        $user = $request->user();
        $isSeller = $user && (int) $this->seller_id === (int) $user->id;
        $isBuyer = $user && (int) $this->buyer_id === (int) $user->id;
        $status = strtolower((string) $this->status);
        $closed = in_array($status, ['completed', 'cancelled', 'canceled'], true);
        $deliveries = collect($this->metadata['deliveries'] ?? [])
            ->map(fn (array $delivery) => [
                'id' => $delivery['id'] ?? null,
                'message' => $delivery['message'] ?? '',
                'status' => $delivery['status'] ?? 'submitted',
                'statusLabel' => str($delivery['status'] ?? 'submitted')->replace('_', ' ')->title()->toString(),
                'submittedAt' => $this->formatIsoDate($delivery['submittedAt'] ?? null),
                'acceptedAt' => $this->formatIsoDate($delivery['acceptedAt'] ?? null),
                'revisionRequestedAt' => $this->formatIsoDate($delivery['revisionRequestedAt'] ?? null),
                'revisionMessage' => $delivery['revisionMessage'] ?? '',
                'files' => array_values($delivery['files'] ?? []),
            ])
            ->values();

        return [
            'deliveries' => $deliveries->all(),
            'latest' => $deliveries->last(),
            'canStartWork' => (bool) (
                $isSeller
                && $status === 'requirements submitted'
                && $this->requirementsAreSubmitted()
                && ! $closed
            ),
            'canSubmitDelivery' => (bool) ($isSeller && in_array($status, ['in progress', 'revision requested'], true)),
            'canRequestRevision' => (bool) ($isBuyer && $status === 'delivered'),
            'canComplete' => (bool) ($isBuyer && $status === 'delivered'),
        ];
    }

    private function formatIsoDate(?string $date): ?string
    {
        if (! $date) {
            return null;
        }

        return Carbon::parse($date)->format('M j, Y g:i A');
    }

    private function activityPayload(): array
    {
        if ($this->relationLoaded('activities') && $this->activities->isNotEmpty()) {
            return $this->activities
                ->sortByDesc('created_at')
                ->map(fn ($activity) => [
                    'id' => $activity->id,
                    'type' => $activity->type,
                    'title' => $activity->title,
                    'detail' => $activity->detail,
                    'time' => $activity->created_at?->format('M j, Y g:i A'),
                    'actorName' => $activity->adminActor?->name ?? $activity->actor?->name ?? 'BDGigs',
                    'actorInitials' => initialsFromOrderName($activity->adminActor?->name ?? $activity->actor?->name ?? 'BDGigs'),
                    'actorAvatar' => $this->assetPath($activity->adminActor?->avatar ?? $activity->actor?->avatar),
                    'icon' => $this->activityIcon($activity->type),
                    'color' => $this->activityColor($activity->type),
                ])
                ->values()
                ->all();
        }

        return collect($this->baseActivityRows())
            ->sortByDesc('sortAt')
            ->map(function (array $row) {
                unset($row['sortAt']);

                return $row;
            })
            ->values()
            ->all();
    }

    private function baseActivityRows(): array
    {
        $rows = [];
        $buyerName = $this->buyer?->name ?: $this->buyer_name ?: 'Buyer';

        if ($this->created_at) {
            $rows[] = $this->activityRowFromRecord(
                'order-created-'.$this->id,
                'order_created',
                'Order created',
                $buyerName.' placed this order for '.$this->service.'.',
                $this->created_at,
                $buyerName,
                $this->buyer?->avatar,
            );
        }

        if ($this->manualPaymentSubmission) {
            $paymentStatus = str($this->manualPaymentSubmission->status)->replace('_', ' ')->title()->toString();
            $rows[] = $this->activityRowFromRecord(
                'payment-'.$this->manualPaymentSubmission->id,
                'payment_'.$this->manualPaymentSubmission->status,
                $paymentStatus === 'Approved' ? 'Payment completed' : 'Payment submitted',
                $buyerName.' submitted payment details. Current payment status: '.$paymentStatus.'.',
                $this->manualPaymentSubmission->created_at ?: $this->created_at,
                $buyerName,
                $this->buyer?->avatar,
            );
        }

        if (! empty($this->metadata['requirementsSubmittedAt'])) {
            $submittedAt = Carbon::parse($this->metadata['requirementsSubmittedAt']);
            $rows[] = $this->activityRowFromRecord(
                'requirements-'.$this->id,
                'requirements_submitted',
                'Requirements submitted',
                $buyerName.' submitted the buyer requirements.',
                $submittedAt,
                $buyerName,
                $this->buyer?->avatar,
            );
        }

        return $rows;
    }

    private function activityRowFromRecord(
        string $id,
        string $type,
        string $title,
        string $detail,
        Carbon $time,
        string $actorName,
        ?string $actorAvatar
    ): array {
        return [
            'id' => $id,
            'type' => $type,
            'title' => $title,
            'detail' => $detail,
            'time' => $time->format('M j, Y g:i A'),
            'sortAt' => $time->getTimestamp(),
            'actorName' => $actorName,
            'actorInitials' => initialsFromOrderName($actorName),
            'actorAvatar' => $this->assetPath($actorAvatar),
            'icon' => $this->activityIcon($type),
            'color' => $this->activityColor($type),
        ];
    }

    private function requirementsPayload(): array
    {
        $items = collect($this->metadata['requirements'] ?? []);

        if ($items->isEmpty()) {
            $items = collect($this->gig?->requirements ?: []);
        }

        return $items
            ->map(fn (array $item, int $index) => $this->requirementRow($item, $index))
            ->values()
            ->all();
    }

    private function requirementsState(Request $request): array
    {
        $items = collect($this->requirementsPayload());
        $submitted = (bool) ($this->metadata['requirementsSubmittedAt'] ?? false)
            || (
                $items->isNotEmpty()
                && $items
                    ->filter(fn (array $item) => $item['required'])
                    ->every(fn (array $item) => $this->requirementHasAnswer($item))
            );
        $user = $request->user();
        $isBuyer = $user && (int) $this->buyer_id === (int) $user->id;
        $isClosed = in_array(strtolower((string) $this->status), ['completed', 'cancelled', 'canceled'], true);

        return [
            'items' => $items->all(),
            'submitted' => $submitted,
            'submittedAt' => $this->metadata['requirementsSubmittedAt'] ?? null,
            'statusLabel' => $submitted ? 'Requirements submitted' : 'Waiting for Requirements',
            'canSubmit' => (bool) ($isBuyer && $items->isNotEmpty() && ! $isClosed),
            'canEdit' => (bool) ($isBuyer && $submitted && ! $isClosed),
        ];
    }

    private function cancellationPayload(Request $request, $latestCancellation): array
    {
        $user = $request->user();
        $isBuyer = $user && (int) $this->buyer_id === (int) $user->id;
        $isSeller = $user && (int) $this->seller_id === (int) $user->id;
        $isParticipant = $isBuyer || $isSeller;
        $closed = in_array(strtolower((string) $this->status), ['completed', 'cancelled', 'canceled'], true);
        $pending = $latestCancellation && $latestCancellation->status === 'cancellation_requested';

        return [
            'status' => $this->cancellation_status,
            'refundStatus' => $this->refund_status,
            'canRequest' => (bool) ($isParticipant && ! $closed && ! $pending),
            'canRespond' => (bool) ($isParticipant && $pending && (int) $latestCancellation->requester_id !== (int) $user?->id),
            'latest' => $latestCancellation ? [
                'id' => $latestCancellation->id,
                'status' => $latestCancellation->status,
                'statusLabel' => str($latestCancellation->status)->replace('_', ' ')->title()->toString(),
                'reason' => $latestCancellation->reason,
                'responseNote' => $latestCancellation->response_note,
                'requestedAt' => $latestCancellation->requested_at?->format('M j, Y g:i A'),
                'respondedAt' => $latestCancellation->responded_at?->format('M j, Y g:i A'),
                'requesterName' => $latestCancellation->requester?->name,
                'responderName' => $latestCancellation->responder?->name,
            ] : null,
        ];
    }

    private function requirementRow(array $item, int $index): array
    {
        $question = $item['question'] ?? $item['label'] ?? 'Requirement';
        $required = (bool) ($item['required'] ?? ! ($item['optional'] ?? false));

        return [
            'id' => (string) ($item['id'] ?? str($question)->slug()->toString() ?: 'requirement-'.$index),
            'question' => $question,
            'label' => $question,
            'type' => $item['type'] ?? 'Free text',
            'required' => $required,
            'optional' => ! $required,
            'allowMultiple' => (bool) ($item['allowMultiple'] ?? false),
            'options' => array_values($item['options'] ?? []),
            'answer' => $item['answer'] ?? '',
            'files' => array_values($item['files'] ?? []),
            'submittedAt' => $item['submittedAt'] ?? null,
        ];
    }

    private function requirementHasAnswer(array $item): bool
    {
        if (strtolower((string) $item['type']) === 'file upload') {
            return count($item['files'] ?? []) > 0;
        }

        if (is_array($item['answer'] ?? null)) {
            return count(array_filter($item['answer'])) > 0;
        }

        return trim((string) ($item['answer'] ?? '')) !== '';
    }

    private function timeExtensionPayload(Request $request, Collection $timeExtensions): array
    {
        $pending = $timeExtensions
            ->where('status', 'pending')
            ->sortByDesc('created_at')
            ->first();
        $latest = $timeExtensions
            ->sortByDesc('created_at')
            ->first();
        $user = $request->user();
        $isSeller = $user && (int) $this->seller_id === (int) $user->id;
        $isBuyer = $user && (int) $this->buyer_id === (int) $user->id;
        $isAdmin = $user && ($user->can('orders.view') || $user->can('orders.manage'));

        return [
            'pending' => $pending ? $this->timeExtensionRow($pending) : null,
            'latest' => $latest ? $this->timeExtensionRow($latest) : null,
            'canRequest' => $isSeller && ! $pending && ! in_array($this->status, ['Completed', 'Cancelled', 'Canceled'], true),
            'canDecide' => (bool) ($pending && ($isBuyer || $isAdmin)),
        ];
    }

    private function timeExtensionRow($extension): array
    {
        return [
            'id' => $extension->id,
            'days' => $extension->days_requested,
            'reason' => $extension->reason,
            'status' => $extension->status,
            'statusLabel' => str($extension->status)->replace('_', ' ')->title()->toString(),
            'originalDueDate' => $extension->original_due_date?->format('M j, Y'),
            'requestedDueDate' => $extension->requested_due_date?->format('M j, Y'),
            'requestedDueAt' => $extension->requested_due_date?->copy()->endOfDay()->toISOString(),
            'requestedByName' => $extension->requester?->name,
            'reviewedByName' => $extension->reviewer?->name,
            'requestedAt' => $extension->created_at?->format('M j, Y g:i A'),
            'decidedAt' => $extension->decided_at?->format('M j, Y g:i A'),
        ];
    }

    private function faqPayload(Request $request): array
    {
        $serviceTitle = $this->service ?: 'this order';
        $isSeller = $request->user() && (int) $this->seller_id === (int) $request->user()->id;

        return [
            [
                'question' => $isSeller ? 'What should I do next?' : 'What happens after I submit requirements?',
                'answer' => $isSeller
                    ? 'Review submitted requirements, keep updates in the order conversation, and submit delivery before the deadline.'
                    : 'The seller is notified immediately. If the order was waiting for requirements, it moves into progress after your submission.',
            ],
            [
                'question' => 'When should I open a Resolution Center case?',
                'answer' => 'Open a case when buyer and seller cannot resolve scope, delivery, revision, or payment concerns inside the order conversation.',
            ],
            [
                'question' => 'Can I still message the other party?',
                'answer' => 'Yes. Keep order-related discussion in the order conversation and add important evidence inside the Resolution Center.',
            ],
            [
                'question' => 'What information helps support review '.$serviceTitle.'?',
                'answer' => 'Include the requested outcome, links or files already shared, delivery dates, revision notes, and any agreement made before the issue started.',
            ],
        ];
    }

    private function resolutionPayload(Request $request, Collection $disputes): array
    {
        $user = $request->user();
        $activeDispute = $disputes
            ->whereNotIn('status', ['resolved', 'rejected', 'closed'])
            ->sortByDesc('created_at')
            ->first();

        return [
            'canOpen' => (bool) ($user && ! $activeDispute && $this->isOrderParticipant($user)),
            'activeCaseCode' => $activeDispute?->case_code,
            'cases' => $disputes
                ->sortByDesc('created_at')
                ->map(fn ($dispute) => [
                    'id' => $dispute->id,
                    'caseCode' => $dispute->case_code,
                    'reason' => $dispute->reason,
                    'description' => $dispute->description,
                    'priority' => $dispute->priority,
                    'status' => $dispute->status,
                    'statusLabel' => str($dispute->status)->replace('_', ' ')->title()->toString(),
                    'isTerminal' => $dispute->isTerminal(),
                    'openedByName' => $dispute->openedByAdmin?->name ?? $dispute->openedBy?->name ?? 'System',
                    'openedAt' => $dispute->created_at?->format('M j, Y g:i A'),
                    'attachments' => array_values($dispute->metadata['attachments'] ?? []),
                    'messages' => $dispute->activities
                        ->sortBy('created_at')
                        ->map(fn ($activity) => [
                            'id' => $activity->id,
                            'type' => $activity->type,
                            'title' => $activity->title,
                            'body' => $activity->detail,
                            'actorName' => $activity->adminActor?->name ?? $activity->actor?->name ?? 'System',
                            'actorInitials' => initialsFromOrderName($activity->adminActor?->name ?? $activity->actor?->name ?? 'System'),
                            'actorAvatar' => $this->assetPath($activity->adminActor?->avatar ?? $activity->actor?->avatar),
                            'time' => $activity->created_at?->format('M j, Y g:i A'),
                        ])
                        ->values()
                        ->all(),
                ])
                ->values()
                ->all(),
        ];
    }

    private function reviewsPayload(Request $request, Collection $reviews): array
    {
        $user = $request->user();
        $buyerReview = $reviews->firstWhere('role', 'buyer');
        $sellerReview = $reviews->firstWhere('role', 'seller');
        $completed = strtolower((string) $this->status) === 'completed';
        $deadline = $this->reviewDeadline();
        $deadlinePassed = now()->gt($deadline);
        $isBuyer = $user && (int) $this->buyer_id === (int) $user->id;
        $isSeller = $user && (int) $this->seller_id === (int) $user->id;
        $bothSubmitted = $buyerReview && $sellerReview;

        return [
            'completed' => $completed,
            'deadlineAt' => $deadline->toISOString(),
            'deadlineLabel' => $deadline->format('M j, Y'),
            'deadlinePassed' => $deadlinePassed,
            'buyerSubmitted' => (bool) $buyerReview,
            'sellerSubmitted' => (bool) $sellerReview,
            'canReview' => $completed
                && ! $deadlinePassed
                && (
                    ($isBuyer && ! $buyerReview)
                    || ($isSeller && $buyerReview && ! $sellerReview)
                ),
            'nextStep' => $this->reviewNextStep($completed, $deadlinePassed, $buyerReview, $sellerReview, $isBuyer, $isSeller),
            'visibleReviews' => [
                'buyer' => $this->reviewVisibleToUser($buyerReview, $bothSubmitted, $isBuyer) ? $this->reviewRow($buyerReview) : null,
                'seller' => $this->reviewVisibleToUser($sellerReview, $bothSubmitted, $isSeller) ? $this->reviewRow($sellerReview) : null,
            ],
        ];
    }

    private function reviewNextStep(bool $completed, bool $deadlinePassed, $buyerReview, $sellerReview, bool $isBuyer, bool $isSeller): string
    {
        if (! $completed) {
            return 'Reviews open after the order is completed.';
        }

        if ($deadlinePassed) {
            return 'The 15 day review deadline has passed.';
        }

        if (! $buyerReview) {
            return $isBuyer
                ? 'Share your review first. The seller can review after you submit.'
                : 'Waiting for the buyer to submit the first review.';
        }

        if (! $sellerReview) {
            return $isSeller
                ? 'The buyer submitted a review. Submit yours to unlock both reviews.'
                : 'Waiting for the seller review. Reviews become visible after both sides submit.';
        }

        return 'Both reviews are complete and visible.';
    }

    private function reviewVisibleToUser($review, bool $bothSubmitted, bool $ownReview): bool
    {
        return (bool) ($review && ($bothSubmitted || $ownReview));
    }

    private function reviewRow($review): array
    {
        return [
            'id' => $review->id,
            'role' => $review->role,
            'rating' => $review->rating,
            'comment' => $review->comment,
            'reviewerName' => $review->reviewer?->name ?? 'Member',
            'reviewerInitials' => initialsFromOrderName($review->reviewer?->name ?? 'Member'),
            'reviewerAvatar' => $this->assetPath($review->reviewer?->avatar),
            'submittedAt' => $review->submitted_at?->format('M j, Y g:i A'),
        ];
    }

    private function assetPath(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
            return $path;
        }

        if (str_starts_with($path, 'assets/') || str_starts_with($path, 'uploads/') || str_starts_with($path, 'storage/')) {
            return '/'.$path;
        }

        return '/storage/'.$path;
    }

    private function reviewDeadline(): Carbon
    {
        if ($this->review_period_expires_at) {
            return $this->review_period_expires_at->copy();
        }

        $completionActivity = $this->relationLoaded('activities')
            ? $this->activities
                ->filter(fn ($activity) => str_contains(strtolower((string) $activity->detail), 'to completed'))
                ->sortByDesc('created_at')
                ->first()
            : null;

        return ($completionActivity?->created_at ?: $this->updated_at ?: $this->created_at ?: now())
            ->copy()
            ->addDays(15)
            ->endOfDay();
    }

    private function isOrderParticipant($user): bool
    {
        return (int) $this->buyer_id === (int) $user->id
            || (int) $this->seller_id === (int) $user->id
            || $user->can('orders.manage');
    }

    private function requirementsAreSubmitted(): bool
    {
        if (! empty($this->metadata['requirementsSubmittedAt'])) {
            return true;
        }

        $items = collect($this->requirementsPayload());

        if ($items->isEmpty()) {
            return true;
        }

        return $items
            ->filter(fn (array $item) => (bool) ($item['required'] ?? false))
            ->every(fn (array $item) => $this->requirementHasAnswer($item));
    }

    private function activityIcon(?string $type): string
    {
        $type = (string) $type;

        return match (true) {
            str_contains($type, 'time_extension') => 'clock',
            str_contains($type, 'resolution') => 'message',
            str_contains($type, 'review') => 'star',
            str_contains($type, 'requirement') => 'orders',
            str_contains($type, 'custom_offer') => 'packageCheck',
            str_contains($type, 'delivery') || str_contains($type, 'revision') => 'packageCheck',
            str_contains($type, 'completed') => 'packageCheck',
            default => 'orders',
        };
    }

    private function activityColor(?string $type): string
    {
        $type = (string) $type;

        if (str_contains((string) $type, 'rejected')) {
            return 'pink';
        }

        return match (true) {
            str_contains($type, 'time_extension') => 'blue',
            str_contains($type, 'resolution') => 'pink',
            str_contains($type, 'review') => 'yellow',
            str_contains($type, 'requirement') => 'violet',
            str_contains($type, 'custom_offer') => 'blue',
            str_contains($type, 'revision') => 'yellow',
            str_contains($type, 'delivery') || str_contains($type, 'completed') => 'green',
            default => 'green',
        };
    }
}

function initialsFromOrderName(string $name): string
{
    return collect(explode(' ', trim($name)))
        ->filter()
        ->take(2)
        ->map(fn (string $part) => mb_substr($part, 0, 1))
        ->implode('');
}
