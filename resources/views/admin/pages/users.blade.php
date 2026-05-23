@extends('admin.layouts.panel')

@section('title', 'Users')

@section('panel')
    @include('admin.partials.stats', ['stats' => $stats])

    <section class="admin-page-grid">
        <article class="admin-panel admin-table-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>User directory</h2>
                    <p>Review account health, profile type, verification, and region.</p>
                </div>
            </div>
            <form class="admin-user-filter-form" method="GET" action="{{ route('admin.users') }}">
                <label>
                    <span>User search</span>
                    <input type="search" name="q" value="{{ $searchQuery }}" placeholder="Search name, username, email, or country">
                </label>
                <label>
                    <span>Marketplace profile</span>
                    <select name="type">
                        @foreach ($filters as $filter)
                            <option value="{{ $filter['value'] }}" @selected($currentFilter === $filter['value'])>
                                {{ $filter['label'] }} ({{ number_format($filter['count']) }})
                            </option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span>Account state</span>
                    <select name="status">
                        @foreach ($statusFilters as $filter)
                            <option value="{{ $filter['value'] }}" @selected($currentStatus === $filter['value'])>
                                {{ $filter['label'] }}
                            </option>
                        @endforeach
                    </select>
                </label>
                <div>
                    <button type="submit">Apply filters</button>
                    @if ($searchQuery !== '' || $currentFilter !== 'all' || $currentStatus !== 'all')
                    <a href="{{ route('admin.users') }}">Clear</a>
                    @endif
                </div>
            </form>
            <div class="admin-filter-summary">
                <strong>{{ number_format($pagination['total']) }}</strong>
                <span>users match the current search, profile, and account state.</span>
            </div>
            <div class="admin-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Profile type</th>
                            <th>Seller level</th>
                            <th>Country</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr>
                                <td><a class="admin-user-table-name" href="{{ route('admin.users.show', $user['id']) }}">{{ $user['name'] }}</a></td>
                                <td>{{ $user['email'] }}</td>
                                <td>{{ $user['profile_type'] }}</td>
                                <td>{{ $user['seller_level'] }}</td>
                                <td>{{ $user['country'] }}</td>
                                <td><span class="admin-status-badge {{ $user['status_class'] }}">{{ $user['status'] }}</span></td>
                                <td>{{ $user['joined'] }}</td>
                                <td>
                                    <details class="admin-action-menu">
                                        <summary>Actions</summary>
                                        <div>
                                            <a href="{{ route('admin.users.show', $user['id']) }}">View details</a>
                                            @if ($user['can_impersonate'])
                                                <form method="POST" action="{{ route('admin.users.impersonate', $user['id']) }}">
                                                    @csrf
                                                    <button type="submit">Login as user</button>
                                                </form>
                                            @endif
                                            @can('users.verify')
                                                <form method="POST" action="{{ route('admin.users.verify', $user['id']) }}">
                                                    @csrf
                                                    <button type="submit">Verify account</button>
                                                </form>
                                            @endcan
                                            @can('users.suspend')
                                                @if ($user['can_restore'])
                                                    <form method="POST" action="{{ route('admin.users.restore', $user['id']) }}">
                                                        @csrf
                                                        <button type="submit">Restore account</button>
                                                    </form>
                                                @elseif ($user['can_suspend'])
                                                    <form method="POST" action="{{ route('admin.users.suspend', $user['id']) }}">
                                                        @csrf
                                                        <button class="is-danger" type="submit">Suspend account</button>
                                                    </form>
                                                @endif
                                            @endcan
                                        </div>
                                    </details>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">No users matched your filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('admin.partials.pagination', ['pagination' => $pagination, 'label' => 'Users pagination'])
        </article>

        <aside class="admin-panel admin-side-insights">
            <div class="admin-panel-head">
                <div>
                    <h2>Verification focus</h2>
                    <p>Highest priority account checks.</p>
                </div>
            </div>
            <ul>
                @foreach ($verificationFocus as $item)
                    <li><strong>{{ $item['value'] }}</strong><span>{{ $item['label'] }}</span></li>
                @endforeach
            </ul>
        </aside>
    </section>

    <section class="admin-workflow-grid">
        <article class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Seller verification pipeline</h2>
                    <p>Where account approvals are currently blocked.</p>
                </div>
            </div>
            <div class="admin-quality-bars">
                @foreach ($verificationPipeline as $item)
                    <span style="--value: {{ $item['value'] }}%"><b>{{ $item['label'] }}</b><em>{{ $item['value'] }}%</em></span>
                @endforeach
            </div>
        </article>

        <article class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Account interventions</h2>
                    <p>Recommended follow-up actions.</p>
                </div>
            </div>
            <div class="admin-workflow-steps">
                <span><b>1</b><strong>Verify review users</strong><small>{{ $verificationFocus[0]['value'] ?? 0 }} pending</small></span>
                <span><b>2</b><strong>Restore trusted accounts</strong><small>{{ $verificationFocus[1]['value'] ?? 0 }} suspended</small></span>
                <span><b>3</b><strong>Complete email checks</strong><small>{{ $verificationFocus[2]['value'] ?? 0 }} unverified</small></span>
            </div>
        </article>
    </section>
@endsection
