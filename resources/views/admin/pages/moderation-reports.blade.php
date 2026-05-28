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
                <div><button type="submit">Apply filters</button><a href="{{ route('admin.moderation-reports') }}">Clear</a></div>
            </form>

            <div class="admin-table-wrap">
                <table>
                    <thead><tr><th>Report</th><th>Type</th><th>Reporter</th><th>Reported user</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        @forelse ($reports as $report)
                            <tr>
                                <td><strong>{{ $report->code }}</strong><small>{{ str($report->reason)->limit(90) }}</small></td>
                                <td>{{ str($report->type)->title() }}</td>
                                <td>{{ $report->reporter?->name ?? 'Unknown' }}</td>
                                <td>{{ $report->reportedUser?->name ?? 'None' }}</td>
                                <td><span class="admin-status-badge {{ in_array($report->status, ['resolved'], true) ? 'is-good' : ($report->status === 'rejected' ? 'is-danger' : 'is-warn') }}">{{ str($report->status)->title() }}</span></td>
                                <td><a class="admin-panel-link" href="{{ route('admin.moderation-reports.show', $report) }}">Open</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="6">No moderation reports matched this queue.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @include('admin.partials.pagination', ['pagination' => $pagination, 'label' => 'Moderation report pagination'])
        </article>
    </section>
@endsection
