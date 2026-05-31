@extends('admin.layouts.panel')

@section('title', 'Moderation Reports')

@section('panel')
    @include('admin.partials.stats', ['stats' => $stats])

    <section class="admin-page-grid">
        <article class="admin-panel admin-table-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Report queue</h2>
                    <p>User-submitted reports across profiles, gigs, orders, and messages.</p>
                </div>
            </div>

            <form class="admin-user-filter-form" method="GET" action="{{ route('admin.moderation-reports') }}">
                <label><span>Search</span><input type="search" name="q" value="{{ $searchQuery }}" placeholder="Report code, reason, or user"></label>
                <label>
                    <span>Status</span>
                    <select name="status">
                        @foreach ($statusFilters as $filter)
                            <option value="{{ $filter['value'] }}" @selected($currentStatus === $filter['value'])>{{ $filter['label'] }} ({{ $filter['count'] }})</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span>Type</span>
                    <select name="type">
                        @foreach ($typeFilters as $filter)
                            <option value="{{ $filter['value'] }}" @selected($currentType === $filter['value'])>{{ $filter['label'] }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span>Assignee</span>
                    <select name="assignee">
                        @foreach ($assigneeFilters as $value => $label)
                            <option value="{{ $value }}" @selected($filterState['assignee'] === $value)>{{ $label }}</option>
                        @endforeach
                        @foreach ($assignees as $assignee)
                            <option value="{{ $assignee->id }}" @selected((string) $filterState['assignee'] === (string) $assignee->id)>{{ $assignee->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span>Age</span>
                    <select name="age">
                        @foreach ($ageFilters as $value => $label)
                            <option value="{{ $value }}" @selected($filterState['age'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span>Sort</span>
                    <select name="sort">
                        @foreach ($sortOptions as $value => $label)
                            <option value="{{ $value }}" @selected($filterState['sort'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <div>
                    <button type="submit">Apply filters</button>
                    @if ($hasActiveFilters)
                        <a href="{{ route('admin.moderation-reports') }}">Clear</a>
                    @endif
                </div>
            </form>

            <div class="admin-filter-row">
                @foreach ($statusFilters as $filter)
                    <a
                        class="{{ $currentStatus === $filter['value'] ? 'is-active' : '' }}"
                        href="{{ request()->fullUrlWithQuery(['status' => $filter['value'], 'page' => 1]) }}"
                    >
                        {{ $filter['label'] }} <span>{{ number_format($filter['count']) }}</span>
                    </a>
                @endforeach
            </div>

            <div class="admin-filter-summary">
                <strong>{{ number_format($pagination['total']) }}</strong>
                <span>moderation reports match the current status, type, assignee, age, and search filters.</span>
            </div>

            @if ($canBulkManage)
                <form method="POST" action="{{ route('admin.moderation-reports.bulk') }}" data-admin-report-bulk-form>
                    @csrf
                    @method('PATCH')
                    <div class="admin-bulk-toolbar">
                        <label>
                            <span>Bulk actions</span>
                            <select name="status" required>
                                <option value="">Change status to...</option>
                                @foreach ($statusOptions as $status)
                                    <option value="{{ $status }}">{{ str($status)->title() }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>
                            <span>Resolution note</span>
                            <input type="text" name="note" value="{{ old('note') }}" placeholder="Optional note for selected reports">
                        </label>
                        <button type="submit">Apply</button>
                        <span data-admin-report-selected-count>0 selected</span>
                    </div>
            @endif

            <div class="admin-table-wrap">
                <table class="admin-report-table">
                    <thead>
                        <tr>
                            @if ($canBulkManage)
                                <th class="admin-table-select-cell">
                                    <input type="checkbox" data-admin-report-select-all aria-label="Select all moderation reports on this page">
                                </th>
                            @endif
                            <th>Report</th>
                            <th>Type</th>
                            <th>Reporter</th>
                            <th>Reported user</th>
                            <th>Status</th>
                            <th>Assigned</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reports as $report)
                            <tr>
                                @if ($canBulkManage)
                                    <td class="admin-table-select-cell">
                                        <input type="checkbox" name="reports[]" value="{{ $report->code }}" data-admin-report-row-check aria-label="Select {{ $report->code }}">
                                    </td>
                                @endif
                                <td><strong>{{ $report->code }}</strong><small>{{ str($report->reason)->limit(90) }}</small></td>
                                <td>{{ str($report->type)->title() }}</td>
                                <td>{{ $report->reporter?->name ?? 'Unknown' }}</td>
                                <td>{{ $report->reportedUser?->name ?? 'None' }}</td>
                                <td><span class="admin-status-badge {{ in_array($report->status, ['resolved'], true) ? 'is-good' : ($report->status === 'rejected' ? 'is-danger' : 'is-warn') }}">{{ str($report->status)->title() }}</span></td>
                                <td>{{ $report->assignedAdmin?->name ?? $report->assignedTo?->name ?? 'Unassigned' }}</td>
                                <td><a class="admin-panel-link" href="{{ route('admin.moderation-reports.show', $report) }}">Open</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ $canBulkManage ? 8 : 7 }}">No moderation reports matched this queue.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($canBulkManage)
                </form>
            @endif

            @include('admin.partials.pagination', ['pagination' => $pagination, 'label' => 'Moderation report pagination'])
        </article>
    </section>
    @if ($canBulkManage)
        @include('admin.partials.bulk-select-scripts', [
            'form' => '[data-admin-report-bulk-form]',
            'selectAll' => '[data-admin-report-select-all]',
            'rowCheck' => '[data-admin-report-row-check]',
            'selectedCount' => '[data-admin-report-selected-count]',
            'itemName' => 'moderation report',
        ])
    @endif
@endsection
