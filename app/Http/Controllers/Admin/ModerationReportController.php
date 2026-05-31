<?php

namespace App\Http\Controllers\Admin;

use App\Models\Admin;
use App\Models\ModerationReport;
use App\Services\ModerationReportService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ModerationReportController extends AdminController
{
    private const ASSIGNEE_FILTERS = [
        'all' => 'Any assignee',
        'unassigned' => 'Unassigned',
        'mine' => 'Assigned to me',
    ];

    private const AGE_FILTERS = [
        'all' => 'Any age',
        'today' => 'Reported today',
        '7d' => 'Reported in 7 days',
        'older_7d' => 'Older than 7 days',
    ];

    private const SORT_OPTIONS = [
        'latest' => 'Newest first',
        'oldest' => 'Oldest first',
        'updated' => 'Recently updated',
        'resolved' => 'Recently resolved',
    ];

    public function index(Request $request)
    {
        $filterState = $this->filterState($request);

        $query = ModerationReport::query()
            ->with(['reporter', 'reportedUser', 'assignedTo', 'assignedAdmin', 'resolvedBy', 'resolvedByAdmin']);

        $this->applyFilters($query, $filterState, $request->user('admin'));
        $this->applySort($query, $filterState['sort']);

        $perPage = 12;
        $total = (clone $query)->count();
        $pagination = $this->paginationMeta($total, $perPage);
        $reports = $query
            ->skip(($pagination['currentPage'] - 1) * $perPage)
            ->take($perPage)
            ->get();

        return $this->panelView('admin.pages.moderation-reports', [
            'pageTitle' => 'Moderation Reports',
            'pageEyebrow' => 'Trust queue',
            'pageDescription' => 'Review user, gig, order, and message reports from the marketplace.',
            'searchPlaceholder' => 'Search reports, users, or reasons',
            'stats' => [
                ['label' => 'Pending', 'value' => number_format(ModerationReport::where('status', 'pending')->count()), 'meta' => 'New reports'],
                ['label' => 'Reviewing', 'value' => number_format(ModerationReport::where('status', 'reviewing')->count()), 'meta' => 'Assigned or active'],
                ['label' => 'Resolved', 'value' => number_format(ModerationReport::where('status', 'resolved')->count()), 'meta' => 'Action taken'],
                ['label' => 'Rejected', 'value' => number_format(ModerationReport::where('status', 'rejected')->count()), 'meta' => 'No action'],
            ],
            'reports' => $reports,
            'pagination' => $pagination,
            'statusFilters' => collect(['all', ...ModerationReport::STATUSES])->map(fn ($filter) => [
                'label' => str($filter)->replace('_', ' ')->title()->toString(),
                'value' => $filter,
                'count' => $filter === 'all' ? ModerationReport::count() : ModerationReport::where('status', $filter)->count(),
            ])->all(),
            'typeFilters' => collect(['all', ...ModerationReport::TYPES])->map(fn ($filter) => [
                'label' => str($filter)->replace('_', ' ')->title()->toString(),
                'value' => $filter,
            ])->all(),
            'currentStatus' => $filterState['status'],
            'currentType' => $filterState['type'],
            'searchQuery' => $filterState['q'],
            'filterState' => $filterState,
            'assigneeFilters' => self::ASSIGNEE_FILTERS,
            'ageFilters' => self::AGE_FILTERS,
            'sortOptions' => self::SORT_OPTIONS,
            'statusOptions' => ModerationReport::STATUSES,
            'assignees' => Admin::permission('admin.access')->orderBy('name')->get(),
            'hasActiveFilters' => $this->hasActiveFilters($filterState),
            'canBulkManage' => $request->user('admin')?->can('reports.manage') ?? false,
        ]);
    }

    public function show(ModerationReport $report)
    {
        $report->load(['reporter', 'reportedUser', 'assignedTo', 'assignedAdmin', 'resolvedBy', 'resolvedByAdmin', 'reportable']);

        return $this->panelView('admin.pages.moderation-report-details', [
            'pageTitle' => 'Report '.$report->code,
            'pageEyebrow' => 'Moderation report',
            'pageDescription' => 'Review the report context, reporter, target, and resolution status.',
            'searchPlaceholder' => 'Search reports',
            'report' => $report,
            'statusOptions' => ModerationReport::STATUSES,
            'stats' => [
                ['label' => 'Type', 'value' => str($report->type)->title()->toString(), 'meta' => 'Report category'],
                ['label' => 'Status', 'value' => str($report->status)->title()->toString(), 'meta' => $report->updated_at?->diffForHumans()],
                ['label' => 'Reporter', 'value' => $report->reporter?->name ?: 'Unknown', 'meta' => $report->reporter?->email ?: 'No email'],
                ['label' => 'Reported user', 'value' => $report->reportedUser?->name ?: 'None', 'meta' => $report->reportedUser?->email ?: 'No target user'],
            ],
        ]);
    }

    public function update(Request $request, ModerationReport $report, ModerationReportService $reports)
    {
        $payload = $request->validate([
            'status' => ['required', 'string', Rule::in(ModerationReport::STATUSES)],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $reports->updateStatus($report, $request->user('admin'), $payload['status'], $payload['note'] ?? null);

        return back()->withNotify('success', 'Moderation report updated.', 'Report updated');
    }

    public function bulkAction(Request $request, ModerationReportService $reports)
    {
        abort_unless($request->user('admin')?->can('reports.manage'), 403);

        $payload = $request->validate([
            'reports' => ['required', 'array', 'min:1'],
            'reports.*' => ['required', 'string', 'distinct', 'exists:moderation_reports,code'],
            'status' => ['required', 'string', Rule::in(ModerationReport::STATUSES)],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $selectedReports = ModerationReport::query()
            ->whereIn('code', $payload['reports'])
            ->get();
        $updated = 0;

        foreach ($selectedReports as $report) {
            if ($report->status === $payload['status'] && ($payload['note'] ?? null) === null) {
                continue;
            }

            $reports->updateStatus($report, $request->user('admin'), $payload['status'], $payload['note'] ?? null);
            $updated++;
        }

        if ($updated === 0) {
            return back()->withNotify('warning', 'No moderation reports changed.', 'Bulk action skipped');
        }

        return back()->withNotify('success', number_format($updated).' moderation '.($updated === 1 ? 'report' : 'reports').' updated.', 'Bulk action applied');
    }

    private function filterState(Request $request): array
    {
        $assignee = trim((string) $request->query('assignee', 'all'));

        if (! in_array($assignee, array_keys(self::ASSIGNEE_FILTERS), true) && ! Admin::whereKey($assignee)->exists()) {
            $assignee = 'all';
        }

        return [
            'q' => trim((string) $request->query('q', '')),
            'status' => $this->validatedOption($request->query('status', 'pending'), ['all', ...ModerationReport::STATUSES], 'pending'),
            'type' => $this->validatedOption($request->query('type', 'all'), ['all', ...ModerationReport::TYPES], 'all'),
            'assignee' => $assignee,
            'age' => $this->validatedOption($request->query('age', 'all'), array_keys(self::AGE_FILTERS), 'all'),
            'sort' => $this->validatedOption($request->query('sort', 'latest'), array_keys(self::SORT_OPTIONS), 'latest'),
        ];
    }

    private function applyFilters(Builder $query, array $filters, ?Admin $admin): void
    {
        if ($filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if ($filters['type'] !== 'all') {
            $query->where('type', $filters['type']);
        }

        if ($filters['q'] !== '') {
            $search = $filters['q'];

            $query->where(function (Builder $reports) use ($search) {
                $reports
                    ->where('code', 'like', "%{$search}%")
                    ->orWhere('reason', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('reporter', fn (Builder $users) => $users->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                    ->orWhereHas('reportedUser', fn (Builder $users) => $users->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            });
        }

        match ($filters['assignee']) {
            'unassigned' => $query->whereNull('assigned_to_admin_id')->whereNull('assigned_to_id'),
            'mine' => $admin ? $query->where('assigned_to_admin_id', $admin->id) : null,
            'all' => null,
            default => $query->where('assigned_to_admin_id', (int) $filters['assignee']),
        };

        match ($filters['age']) {
            'today' => $query->whereDate('created_at', now()->toDateString()),
            '7d' => $query->where('created_at', '>=', now()->subDays(7)),
            'older_7d' => $query->where('created_at', '<', now()->subDays(7)),
            default => null,
        };
    }

    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'oldest' => $query->oldest(),
            'updated' => $query->orderByDesc('updated_at'),
            'resolved' => $query->orderByDesc('resolved_at')->latest(),
            default => $query->latest(),
        };
    }

    private function hasActiveFilters(array $filters): bool
    {
        return $filters['q'] !== ''
            || $filters['status'] !== 'pending'
            || $filters['type'] !== 'all'
            || $filters['assignee'] !== 'all'
            || $filters['age'] !== 'all'
            || $filters['sort'] !== 'latest';
    }

    private function validatedOption(mixed $value, array $allowed, string $fallback): string
    {
        $value = trim((string) $value);

        return in_array($value, $allowed, true) ? $value : $fallback;
    }
}
