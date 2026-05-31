@extends('admin.layouts.panel')

@section('title', 'Users')

@section('panel')
    @php
        $canBulkManage = auth('admin')->user()?->can('users.verify') || auth('admin')->user()?->can('users.suspend');
    @endphp

    @include('admin.partials.stats', ['stats' => $stats])

    <section class="admin-page-grid">
        <article class="admin-panel admin-table-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>User directory</h2>
                    <p>Filter accounts, inspect lifecycle state, and apply controlled bulk actions.</p>
                </div>
            </div>

            <form class="admin-user-filter-form admin-user-directory-filter-form" method="GET" action="{{ route('admin.users') }}">
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
                            <option value="{{ $filter['value'] }}" @selected($filterState['status'] === $filter['value'])>
                                {{ $filter['label'] }}
                            </option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span>Seller state</span>
                    <select name="seller_status">
                        @foreach ($sellerStatusFilters as $value => $label)
                            <option value="{{ $value }}" @selected($filterState['seller_status'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span>Email state</span>
                    <select name="email">
                        @foreach ($emailFilters as $value => $label)
                            <option value="{{ $value }}" @selected($filterState['email'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span>Country</span>
                    <select name="country">
                        <option value="">All countries</option>
                        @foreach ($countryOptions as $country)
                            <option value="{{ $country }}" @selected($filterState['country'] === $country)>{{ $country }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span>Activity</span>
                    <select name="activity">
                        @foreach ($activityFilters as $value => $label)
                            <option value="{{ $value }}" @selected($filterState['activity'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span>Joined</span>
                    <select name="joined">
                        @foreach ($joinedFilters as $value => $label)
                            <option value="{{ $value }}" @selected($filterState['joined'] === $value)>{{ $label }}</option>
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
                        <a href="{{ route('admin.users') }}">Clear</a>
                    @endif
                </div>
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

            <div class="admin-filter-summary">
                <strong>{{ number_format($pagination['total']) }}</strong>
                <span>users match the current search, profile, account, seller, email, country, and activity filters.</span>
            </div>

            @if ($canBulkManage)
                <form method="POST" action="{{ route('admin.users.bulk') }}" data-admin-user-bulk-form>
                    @csrf
                    <div class="admin-bulk-toolbar">
                        <label>
                            <span>Bulk actions</span>
                            <select name="bulk_action" required>
                                <option value="">Choose action</option>
                                @can('users.verify')
                                    <option value="verify">Verify users</option>
                                @endcan
                                @can('users.suspend')
                                    <option value="suspend">Suspend users</option>
                                    <option value="restore">Restore users</option>
                                    <option value="deactivate">Deactivate users</option>
                                @endcan
                            </select>
                        </label>
                        <label>
                            <span>Action reason</span>
                            <input type="text" name="reason" value="{{ old('reason') }}" placeholder="Required for suspend and deactivate">
                        </label>
                        <button type="submit">Apply</button>
                        <span data-admin-user-selected-count>0 selected</span>
                    </div>
            @endif

            <div class="admin-table-wrap">
                <table class="admin-user-table">
                    <thead>
                        <tr>
                            @if ($canBulkManage)
                                <th class="admin-table-select-cell">
                                    <input type="checkbox" data-admin-user-select-all aria-label="Select all users on this page">
                                </th>
                            @endif
                            <th>User</th>
                            <th>Email</th>
                            <th>Profile</th>
                            <th>Seller status</th>
                            <th>Verification</th>
                            <th>Status</th>
                            <th>Country</th>
                            <th>Activity</th>
                            <th>Volume</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr>
                                @if ($canBulkManage)
                                    <td class="admin-table-select-cell">
                                        <input type="checkbox" name="users[]" value="{{ $user['id'] }}" data-admin-user-row-check aria-label="Select {{ $user['name'] }}">
                                    </td>
                                @endif
                                <td>
                                    <a class="admin-user-table-name" href="{{ route('admin.users.show', $user['id']) }}">{{ $user['name'] }}</a>
                                    <small>{{ $user['joined'] }}</small>
                                </td>
                                <td>
                                    {{ $user['email'] }}
                                    <small>{{ $user['email_verified'] ? 'Email verified' : 'Email unverified' }}</small>
                                </td>
                                <td>{{ $user['profile_type'] }}</td>
                                <td>{{ $user['seller_status'] }}</td>
                                <td>{{ $user['verification'] }}</td>
                                <td><span class="admin-status-badge {{ $user['status_class'] }}">{{ $user['status'] }}</span></td>
                                <td>{{ $user['country'] }}</td>
                                <td>{{ $user['last_seen'] }}</td>
                                <td>
                                    <span>{{ number_format($user['metrics']['gigs']) }} gigs</span>
                                    <small>{{ number_format($user['metrics']['buyer_orders']) }} buyer / {{ number_format($user['metrics']['seller_orders']) }} seller orders</small>
                                </td>
                                <td>
                                    <a class="admin-panel-link" href="{{ route('admin.users.show', $user['id']) }}">View details</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canBulkManage ? 11 : 10 }}">No users matched your filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($canBulkManage)
                </form>
            @endif

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

    @if ($canBulkManage)
        <script>
            document.addEventListener("DOMContentLoaded", () => {
                const form = document.querySelector("[data-admin-user-bulk-form]");

                if (! form) {
                    return;
                }

                const selectAll = form.querySelector("[data-admin-user-select-all]");
                const rowChecks = Array.from(form.querySelectorAll("[data-admin-user-row-check]"));
                const selectedCount = form.querySelector("[data-admin-user-selected-count]");
                const updateSelectionState = () => {
                    const count = rowChecks.filter((checkbox) => checkbox.checked).length;

                    if (selectedCount) {
                        selectedCount.textContent = `${count} selected`;
                    }

                    if (selectAll) {
                        selectAll.checked = count > 0 && count === rowChecks.length;
                        selectAll.indeterminate = count > 0 && count < rowChecks.length;
                    }
                };

                selectAll?.addEventListener("change", () => {
                    rowChecks.forEach((checkbox) => {
                        checkbox.checked = selectAll.checked;
                    });
                    updateSelectionState();
                });

                rowChecks.forEach((checkbox) => {
                    checkbox.addEventListener("change", updateSelectionState);
                });

                form.addEventListener("submit", (event) => {
                    if (rowChecks.length > 0 && ! rowChecks.some((checkbox) => checkbox.checked)) {
                        event.preventDefault();

                        if (window.notify?.error) {
                            window.notify.error("Select at least one user before applying a bulk action.");
                        } else {
                            window.alert("Select at least one user before applying a bulk action.");
                        }
                    }
                });

                updateSelectionState();
            });
        </script>
    @endif
@endsection
