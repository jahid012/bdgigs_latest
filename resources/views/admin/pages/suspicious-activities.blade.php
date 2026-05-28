@extends('admin.layouts.panel')

@section('title', 'Suspicious Activity')

@section('panel')
    @include('admin.partials.stats', ['stats' => $stats])

    <section class="admin-page-grid">
        <article class="admin-panel admin-table-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Security signal queue</h2>
                    <p>Failed login spikes, unusual withdrawals, dispute patterns, and high-volume messaging signals.</p>
                </div>
            </div>

            <form class="admin-user-filter-form" method="GET" action="{{ route('admin.suspicious-activities') }}">
                <label><span>Search</span><input type="search" name="q" value="{{ $searchQuery }}" placeholder="User, IP, type, or description"></label>
                <label>
                    <span>Severity</span>
                    <select name="severity">
                        @foreach ($severityFilters as $filter)
                            <option value="{{ $filter['value'] }}" @selected($currentSeverity === $filter['value'])>{{ $filter['label'] }} ({{ $filter['count'] }})</option>
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
                <div><button type="submit">Apply filters</button><a href="{{ route('admin.suspicious-activities') }}">Clear</a></div>
            </form>

            <div class="admin-table-wrap">
                <table>
                    <thead><tr><th>Signal</th><th>User</th><th>Severity</th><th>IP</th><th>Reviewed</th><th>Actions</th></tr></thead>
                    <tbody>
                        @forelse ($activities as $activity)
                            <tr>
                                <td><strong>{{ str($activity->type)->replace('_', ' ')->title() }}</strong><small>{{ str($activity->description)->limit(90) }}</small></td>
                                <td>{{ $activity->user?->name ?? 'Anonymous' }}</td>
                                <td><span class="admin-status-badge {{ in_array($activity->severity, ['critical', 'high'], true) ? 'is-danger' : 'is-warn' }}">{{ str($activity->severity)->title() }}</span></td>
                                <td>{{ $activity->ip_address ?: 'Unknown' }}</td>
                                <td>{{ $activity->reviewed_at?->format('M j, Y') ?? 'No' }}</td>
                                <td><a class="admin-panel-link" href="{{ route('admin.suspicious-activities.show', $activity) }}">Open</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="6">No suspicious activity matched this queue.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @include('admin.partials.pagination', ['pagination' => $pagination, 'label' => 'Suspicious activity pagination'])
        </article>
    </section>
@endsection
