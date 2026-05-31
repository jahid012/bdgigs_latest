<?php

namespace App\Http\Controllers\Admin;

use App\Models\ManualPaymentMethod;
use App\Models\ManualPaymentSubmission;
use App\Services\ManualPaymentReviewService;
use Illuminate\Http\Request;

class ManualPaymentController extends AdminController
{
    public function index(Request $request)
    {
        $status = trim((string) $request->query('status', 'pending'));
        $allowedStatuses = ['all', 'pending', 'approved', 'rejected'];
        $status = in_array($status, $allowedStatuses, true) ? $status : 'pending';
        $search = trim((string) $request->query('q', ''));
        $query = ManualPaymentSubmission::query()
            ->with(['order', 'buyer', 'method', 'reviewer', 'adminReviewer'])
            ->latest();

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function ($submissionQuery) use ($search) {
                $submissionQuery
                    ->where('reference', 'like', "%{$search}%")
                    ->orWhereHas('order', fn ($orders) => $orders
                        ->where('code', 'like', "%{$search}%")
                        ->orWhere('service', 'like', "%{$search}%"))
                    ->orWhereHas('buyer', fn ($buyers) => $buyers
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
            });
        }

        $perPage = 8;
        $total = (clone $query)->count();
        $pagination = $this->paginationMeta($total, $perPage);
        $submissions = $query
            ->skip(($pagination['currentPage'] - 1) * $perPage)
            ->take($perPage)
            ->get();

        return $this->panelView('admin.pages.manual-payments', [
            'pageTitle' => 'Manual Payments',
            'pageEyebrow' => 'Payment review',
            'pageDescription' => 'Approve buyer payment references before manually paid orders move into delivery.',
            'searchPlaceholder' => 'Search order, buyer, or payment reference',
            'stats' => [
                ['label' => 'Pending review', 'value' => number_format(ManualPaymentSubmission::where('status', 'pending')->count()), 'meta' => 'Needs decision'],
                ['label' => 'Approved', 'value' => number_format(ManualPaymentSubmission::where('status', 'approved')->count()), 'meta' => 'Released to requirements'],
                ['label' => 'Rejected', 'value' => number_format(ManualPaymentSubmission::where('status', 'rejected')->count()), 'meta' => 'Buyer follow-up'],
                ['label' => 'Pending value', 'value' => $this->money((int) ManualPaymentSubmission::where('status', 'pending')->sum('amount_cents')), 'meta' => 'Submitted references'],
            ],
            'filters' => collect($allowedStatuses)->map(fn (string $filter) => [
                'label' => str($filter)->title()->toString(),
                'value' => $filter,
                'count' => $filter === 'all'
                    ? ManualPaymentSubmission::count()
                    : ManualPaymentSubmission::where('status', $filter)->count(),
            ])->all(),
            'currentFilter' => $status,
            'searchQuery' => $search,
            'submissions' => $submissions,
            'methods' => ManualPaymentMethod::query()->orderBy('sort_order')->orderBy('name')->get(),
            'pagination' => $pagination,
        ]);
    }

    public function review(
        Request $request,
        ManualPaymentSubmission $submission,
        ManualPaymentReviewService $reviewer
    ) {
        $payload = $request->validate([
            'decision' => ['required', 'string', 'in:approve,reject'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $reviewer->review(
            $submission->loadMissing('order'),
            $request->user('admin'),
            $payload['decision'],
            $payload['note'] ?? null,
        );

        return back()->withNotify(
            'success',
            'Manual payment '.$payload['decision'].'d.',
            'Payment reviewed',
        );
    }
}
