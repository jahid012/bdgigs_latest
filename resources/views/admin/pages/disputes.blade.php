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
                    <button type="submit">Filter disputes</button>
                    @if ($hasActiveFilters)
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
            <div class="admin-filter-summary">
                <strong>{{ number_format($pagination['total']) }}</strong>
                <span>dispute cases match the current status, priority, assignee, age, and search filters.</span>
            </div>
            @if ($canBulkResolve)
                <form method="POST" action="{{ route('admin.disputes.bulk') }}" data-admin-dispute-bulk-form>
                    @csrf
                    @method('PATCH')
                    <div class="admin-bulk-toolbar">
                        <label>
                            <span>Bulk actions</span>
                            <select name="bulk_action" required>
                                <option value="">Choose action</option>
                                <option value="set_status">Change status</option>
                                <option value="set_priority">Change priority</option>
                                <option value="assign">Assign cases</option>
                            </select>
                        </label>
                        <label>
                            <span>Status</span>
                            <select name="status">
                                <option value="">Keep current</option>
                                @foreach ($statusOptions as $status)
                                    <option value="{{ $status }}">{{ str($status)->replace('_', ' ')->title() }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>
                            <span>Priority</span>
                            <select name="priority">
                                <option value="">Keep current</option>
                                @foreach ($priorityOptions as $priority)
                                    <option value="{{ $priority }}">{{ str($priority)->title() }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>
                            <span>Assign to</span>
                            <select name="assigned_to_id">
                                <option value="">Unassigned</option>
                                @foreach ($assignees as $assignee)
                                    <option value="{{ $assignee->id }}">{{ $assignee->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>
                            <span>Resolution / note</span>
                            <input type="text" name="resolution" value="{{ old('resolution') }}" placeholder="Required when resolving or closing">
                        </label>
                        <label>
                            <span>Activity note</span>
                            <input type="text" name="note" value="{{ old('note') }}" placeholder="Optional internal note">
                        </label>
                        <button type="submit">Apply</button>
                        <span data-admin-dispute-selected-count>0 selected</span>
                    </div>
            @endif
            <div class="admin-table-wrap">
                <table class="admin-dispute-table">
                    <thead>
                        <tr>
                            @if ($canBulkResolve)
                                <th class="admin-table-select-cell">
                                    <input type="checkbox" data-admin-dispute-select-all aria-label="Select all disputes on this page">
                                </th>
                            @endif
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
                                @if ($canBulkResolve)
                                    <td class="admin-table-select-cell">
                                        <input type="checkbox" name="disputes[]" value="{{ $dispute->case_code }}" data-admin-dispute-row-check aria-label="Select {{ $dispute->case_code }}">
                                    </td>
                                @endif
                                <td><a class="admin-user-table-name" href="{{ route('admin.disputes.show', $dispute) }}">{{ $dispute->case_code }}</a></td>
                                <td>#{{ $dispute->order?->code ?? 'Unavailable' }}</td>
                                <td>{{ $dispute->reason }}</td>
                                <td><span class="admin-status-badge {{ $priorityClass }}">{{ str($dispute->priority)->title() }}</span></td>
                                <td><span class="admin-status-badge {{ $statusClass }}">{{ str($dispute->status)->replace('_', ' ')->title() }}</span></td>
                                <td>{{ $dispute->assignedAdmin?->name ?? $dispute->assignedTo?->name ?? 'Unassigned' }}</td>
                                <td><a class="admin-panel-link" href="{{ route('admin.disputes.show', $dispute) }}">View details</a></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canBulkResolve ? 8 : 7 }}">No disputes matched these filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($canBulkResolve)
                </form>
            @endif
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
    @if ($canBulkResolve)
        @include('admin.partials.bulk-select-scripts', [
            'form' => '[data-admin-dispute-bulk-form]',
            'selectAll' => '[data-admin-dispute-select-all]',
            'rowCheck' => '[data-admin-dispute-row-check]',
            'selectedCount' => '[data-admin-dispute-selected-count]',
            'itemName' => 'dispute',
        ])
    @endif

@endsection
