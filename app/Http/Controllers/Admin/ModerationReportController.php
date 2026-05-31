<?php

namespace App\Http\Controllers\Admin;

use App\Models\ModerationReport;
use App\Services\ModerationReportService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ModerationReportController extends AdminController
{
    public function index(Request $request)
    {
        $status = trim((string) $request->query('status', 'pending'));
        $type = trim((string) $request->query('type', 'all'));
        $search = trim((string) $request->query('q', ''));
        $status = in_array($status, ['all', ...ModerationReport::STATUSES], true) ? $status : 'pending';
        $type = in_array($type, ['all', ...ModerationReport::TYPES], true) ? $type : 'all';
        $query = ModerationReport::query()
            ->with(['reporter', 'reportedUser', 'assignedTo', 'assignedAdmin', 'resolvedBy', 'resolvedByAdmin'])
            ->latest();

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($type !== 'all') {
            $query->where('type', $type);
        }

        if ($search !== '') {
            $query->where(function ($reports) use ($search) {
                $reports
                    ->where('code', 'like', "%{$search}%")
                    ->orWhere('reason', 'like', "%{$search}%")
                    ->orWhereHas('reporter', fn ($users) => $users->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                    ->orWhereHas('reportedUser', fn ($users) => $users->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            });
        }

        $perPage = 10;
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
            'currentStatus' => $status,
            'currentType' => $type,
            'searchQuery' => $search,
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
}
