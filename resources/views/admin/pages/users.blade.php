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
            <form class="admin-user-search-form" method="GET" action="{{ route('admin.users') }}">
                <input type="hidden" name="type" value="{{ $currentFilter }}">
                <label>
                    <span>User search</span>
                    <input type="search" name="q" value="{{ $searchQuery }}" placeholder="Search name, email, or country">
                </label>
                <button type="submit">Search users</button>
                @if ($searchQuery !== '' || $currentFilter !== 'all')
                    <a href="{{ route('admin.users') }}">Clear</a>
                @endif
            </form>
            <div class="admin-filter-row">
                @foreach ($filters as $filter)
                    <a
                        class="{{ $currentFilter === $filter['value'] ? 'is-active' : '' }}"
                        href="{{ request()->fullUrlWithQuery(['type' => $filter['value'], 'page' => 1]) }}"
                    >
                        {{ $filter['label'] }} <span>{{ number_format($filter['count']) }}</span>
                    </a>
                @endforeach
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
                                <td>{{ $user['name'] }}</td>
                                <td>{{ $user['email'] }}</td>
                                <td>{{ $user['profile_type'] }}</td>
                                <td>{{ $user['seller_level'] }}</td>
                                <td>{{ $user['country'] }}</td>
                                <td><span class="admin-status-badge {{ $user['status_class'] }}">{{ $user['status'] }}</span></td>
                                <td>{{ $user['joined'] }}</td>
                                <td>
                                    <div class="admin-row-actions">
                                        @can('users.verify')
                                            <form method="POST" action="{{ route('admin.users.verify', $user['id']) }}">
                                                @csrf
                                                <button type="submit">Verify</button>
                                            </form>
                                        @endcan
                                        @can('users.suspend')
                                            @if ($user['can_restore'])
                                                <form method="POST" action="{{ route('admin.users.restore', $user['id']) }}">
                                                    @csrf
                                                    <button type="submit">Restore</button>
                                                </form>
                                            @elseif ($user['can_suspend'])
                                                <form method="POST" action="{{ route('admin.users.suspend', $user['id']) }}">
                                                    @csrf
                                                    <button type="submit">Suspend</button>
                                                </form>
                                            @endif
                                        @endcan
                                    </div>
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
