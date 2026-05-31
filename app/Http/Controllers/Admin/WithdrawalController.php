<?php

namespace App\Http\Controllers\Admin;

use App\Models\WithdrawalRequest;
use App\Services\ManualWithdrawalReviewService;
use Illuminate\Http\Request;

class WithdrawalController extends AdminController
{
    public function index(Request $request)
    {
        $status = trim((string) $request->query('status', 'pending'));
        $allowedStatuses = ['all', 'pending', 'approved', 'paid', 'failed', 'rejected', 'cancelled'];
        $status = in_array($status, $allowedStatuses, true) ? $status : 'pending';
        $search = trim((string) $request->query('q', ''));
        $query = WithdrawalRequest::query()
            ->with(['seller', 'payoutMethod', 'reviewer', 'adminReviewer', 'payer', 'adminPayer'])
            ->latest();

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function ($withdrawals) use ($search) {
                $withdrawals
                    ->where('code', 'like', "%{$search}%")
                    ->orWhere('payment_reference', 'like', "%{$search}%")
                    ->orWhereHas('seller', fn ($sellers) => $sellers
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
            });
        }

        $perPage = 8;
        $total = (clone $query)->count();
        $pagination = $this->paginationMeta($total, $perPage);
        $withdrawals = $query
            ->skip(($pagination['currentPage'] - 1) * $perPage)
            ->take($perPage)
            ->get();

        return $this->panelView('admin.pages.withdrawals', [
            'pageTitle' => 'Withdrawals',
            'pageEyebrow' => 'Manual payouts',
            'pageDescription' => 'Review seller withdrawal requests, record manual payout references, and keep payout history auditable.',
            'searchPlaceholder' => 'Search withdrawal, seller, or payout reference',
            'stats' => [
                ['label' => 'Pending requests', 'value' => number_format(WithdrawalRequest::where('status', 'pending')->count()), 'meta' => 'Needs review'],
                ['label' => 'Approved', 'value' => number_format(WithdrawalRequest::where('status', 'approved')->count()), 'meta' => 'Ready to pay'],
                ['label' => 'Paid to date', 'value' => $this->money($this->paidTotal()), 'meta' => 'Manual references recorded'],
                ['label' => 'Reserved value', 'value' => $this->money((int) WithdrawalRequest::whereIn('status', ['pending', 'under_review', 'approved'])->sum('amount_cents')), 'meta' => 'Held from available balance'],
            ],
            'filters' => collect($allowedStatuses)->map(fn (string $filter) => [
                'label' => str($filter)->replace('_', ' ')->title()->toString(),
                'value' => $filter,
                'count' => $filter === 'all'
                    ? WithdrawalRequest::count()
                    : WithdrawalRequest::where('status', $filter)->count(),
            ])->all(),
            'currentFilter' => $status,
            'searchQuery' => $search,
            'withdrawals' => $withdrawals,
            'pagination' => $pagination,
        ]);
    }

    public function review(
        Request $request,
        WithdrawalRequest $withdrawal,
        ManualWithdrawalReviewService $reviews
    ) {
        $payload = $request->validate([
            'action' => ['required', 'string', 'in:approve,reject,mark_paid,mark_failed'],
            'note' => ['nullable', 'string', 'max:1000'],
            'payment_reference' => ['nullable', 'string', 'max:180', 'required_if:action,mark_paid'],
        ]);

        if ($payload['action'] === 'mark_paid') {
            abort_unless($request->user('admin')?->can('withdrawals.pay'), 403);
        } else {
            abort_unless($request->user('admin')?->can('withdrawals.review'), 403);
        }

        $reviews->decide(
            $withdrawal->loadMissing('seller'),
            $request->user('admin'),
            $payload['action'],
            $payload['note'] ?? null,
            $payload['payment_reference'] ?? null,
        );

        return back()->withNotify('success', 'Withdrawal queue updated.', 'Withdrawal reviewed');
    }

    private function paidTotal(): int
    {
        return (int) WithdrawalRequest::where('status', 'paid')->get()
            ->sum(fn (WithdrawalRequest $withdrawal) => $withdrawal->approved_amount_cents ?: $withdrawal->amount_cents);
    }
}
