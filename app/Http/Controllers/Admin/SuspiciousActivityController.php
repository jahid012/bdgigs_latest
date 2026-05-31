<?php

namespace App\Http\Controllers\Admin;

use App\Models\SuspiciousActivityLog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SuspiciousActivityController extends AdminController
{
    public function index(Request $request)
    {
        $severity = trim((string) $request->query('severity', 'all'));
        $type = trim((string) $request->query('type', 'all'));
        $search = trim((string) $request->query('q', ''));
        $severity = in_array($severity, ['all', ...SuspiciousActivityLog::SEVERITIES], true) ? $severity : 'all';
        $query = SuspiciousActivityLog::query()
            ->with(['user', 'reviewer', 'adminReviewer'])
            ->latest();

        if ($severity !== 'all') {
            $query->where('severity', $severity);
        }

        if ($type !== 'all') {
            $query->where('type', $type);
        }

        if ($search !== '') {
            $query->where(function ($activities) use ($search) {
                $activities
                    ->where('type', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($users) => $users->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            });
        }

        $perPage = 10;
        $total = (clone $query)->count();
        $pagination = $this->paginationMeta($total, $perPage);
        $activities = $query
            ->skip(($pagination['currentPage'] - 1) * $perPage)
            ->take($perPage)
            ->get();
        $types = SuspiciousActivityLog::query()->select('type')->distinct()->pluck('type')->filter()->values();

        return $this->panelView('admin.pages.suspicious-activities', [
            'pageTitle' => 'Suspicious Activity',
            'pageEyebrow' => 'Security review',
            'pageDescription' => 'Investigate high-risk login, withdrawal, dispute, and messaging signals.',
            'searchPlaceholder' => 'Search security signals',
            'stats' => [
                ['label' => 'Critical', 'value' => number_format(SuspiciousActivityLog::where('severity', 'critical')->count()), 'meta' => 'Highest risk'],
                ['label' => 'High', 'value' => number_format(SuspiciousActivityLog::where('severity', 'high')->count()), 'meta' => 'Admin alert'],
                ['label' => 'Unreviewed', 'value' => number_format(SuspiciousActivityLog::whereNull('reviewed_at')->count()), 'meta' => 'Needs triage'],
                ['label' => 'Total', 'value' => number_format(SuspiciousActivityLog::count()), 'meta' => 'All signals'],
            ],
            'activities' => $activities,
            'pagination' => $pagination,
            'severityFilters' => collect(['all', ...SuspiciousActivityLog::SEVERITIES])->map(fn ($filter) => [
                'label' => str($filter)->title()->toString(),
                'value' => $filter,
                'count' => $filter === 'all' ? SuspiciousActivityLog::count() : SuspiciousActivityLog::where('severity', $filter)->count(),
            ])->all(),
            'typeFilters' => collect(['all', ...$types])->map(fn ($filter) => [
                'label' => str($filter)->replace('_', ' ')->title()->toString(),
                'value' => $filter,
            ])->all(),
            'currentSeverity' => $severity,
            'currentType' => $type,
            'searchQuery' => $search,
        ]);
    }

    public function show(SuspiciousActivityLog $activity)
    {
        $activity->load(['user', 'reviewer', 'adminReviewer']);

        return $this->panelView('admin.pages.suspicious-activity-details', [
            'pageTitle' => str($activity->type)->replace('_', ' ')->title()->toString(),
            'pageEyebrow' => 'Security signal',
            'pageDescription' => 'Review context, metadata, device/IP details, and mark signals reviewed.',
            'searchPlaceholder' => 'Search security',
            'activity' => $activity,
            'stats' => [
                ['label' => 'Severity', 'value' => str($activity->severity)->title()->toString(), 'meta' => 'Rule severity'],
                ['label' => 'User', 'value' => $activity->user?->name ?: 'Anonymous', 'meta' => $activity->user?->email ?: 'No user'],
                ['label' => 'IP address', 'value' => $activity->ip_address ?: 'Unknown', 'meta' => 'Request source'],
                ['label' => 'Created', 'value' => $activity->created_at?->format('M j'), 'meta' => $activity->created_at?->diffForHumans()],
            ],
        ]);
    }

    public function review(Request $request, SuspiciousActivityLog $activity)
    {
        $payload = $request->validate([
            'severity' => ['nullable', 'string', Rule::in(SuspiciousActivityLog::SEVERITIES)],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $activity->forceFill([
            'severity' => $payload['severity'] ?? $activity->severity,
            'description' => $payload['description'] ?: $activity->description,
            'reviewed_by' => null,
            'reviewed_by_admin_id' => $request->user('admin')?->id,
            'reviewed_at' => now(),
        ])->save();

        return back()->withNotify('success', 'Security signal marked reviewed.', 'Activity reviewed');
    }
}
