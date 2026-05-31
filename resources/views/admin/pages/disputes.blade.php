@extends('admin.layouts.panel')

@section('title', 'Disputes')

@section('panel')
    @include('admin.partials.stats', ['stats' => $stats])

    <section class="admin-page-grid">
        <article class="admin-panel admin-table-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Resolution queue</h2>
                    <p>Prioritize persisted cases by urgency, order context, and owner.</p>
                </div>
            </div>
            <form class="admin-user-filter-form admin-dispute-filter-form" method="GET" action="{{ route('admin.disputes') }}">
                <label>
                    <span>Case search</span>
                    <input type="search" name="q" value="{{ $searchQuery }}" placeholder="Search case, order, reason, buyer, or seller">
                </label>
                <label>
                    <span>Status</span>
                    <select name="status">
                        @foreach ($statusFilters as $filter)
                            <option value="{{ $filter['value'] }}" @selected($currentStatus === $filter['value'])>{{ $filter['label'] }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span>Priority</span>
                    <select name="priority">
                        @foreach ($priorityFilters as $filter)
                            <option value="{{ $filter['value'] }}" @selected($currentPriority === $filter['value'])>{{ $filter['label'] }}</option>
                        @endforeach
                    </select>
                </label>
                <div>
                    <button type="submit">Filter disputes</button>
                    @if ($searchQuery !== '' || $currentStatus !== 'open' || $currentPriority !== 'all')
                        <a href="{{ route('admin.disputes') }}">Clear</a>
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
            <div class="admin-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Case</th>
                            <th>Order</th>
                            <th>Reason</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Assigned</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($disputes as $dispute)
                            @php
                                $priorityClass = match ($dispute->priority) {
                                    'critical' => 'status-cancelled',
                                    'high' => 'status-delivered',
                                    default => 'status-progress',
                                };
                                $statusClass = in_array($dispute->status, ['resolved', 'closed'], true)
                                    ? 'status-completed'
                                    : ($dispute->status === 'open' ? 'status-cancelled' : 'status-delivered');
                            @endphp
                            <tr>
                                <td>{{ $dispute->case_code }}</td>
                                <td>#{{ $dispute->order?->code ?? 'Unavailable' }}</td>
                                <td>{{ $dispute->reason }}</td>
                                <td><span class="admin-status-badge {{ $priorityClass }}">{{ str($dispute->priority)->title() }}</span></td>
                                <td><span class="admin-status-badge {{ $statusClass }}">{{ str($dispute->status)->replace('_', ' ')->title() }}</span></td>
                                <td>{{ $dispute->assignedAdmin?->name ?? $dispute->assignedTo?->name ?? 'Unassigned' }}</td>
                                <td><a class="admin-panel-link" href="{{ route('admin.disputes.show', $dispute) }}">View details</a></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">No disputes matched these filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('admin.partials.pagination', ['pagination' => $pagination, 'label' => 'Disputes pagination'])
        </article>

        <aside class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Case playbook</h2>
                    <p>Suggested flow for support agents.</p>
                </div>
            </div>
            <ol class="admin-activity-list">
                <li>Open cases from order details when a conflict needs an audit trail.</li>
                <li>Check order requirements, payment review, and linked conversation evidence.</li>
                <li>Assign the case and move the status when waiting on a party.</li>
                <li>Write the resolution before resolving or closing the case.</li>
            </ol>
        </aside>
    </section>

@endsection
