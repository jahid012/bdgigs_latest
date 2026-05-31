@extends('admin.layouts.panel')

@section('title', 'Seller Applications')

@section('panel')
    @include('admin.partials.stats', ['stats' => $stats])

    <section class="admin-page-grid">
        <article class="admin-panel admin-table-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Seller review queue</h2>
                    <p>Review pending sellers and keep approval history auditable.</p>
                </div>
            </div>

            <form class="admin-user-filter-form" method="GET" action="{{ route('admin.seller-applications') }}">
                <label>
                    <span>Search</span>
                    <input type="search" name="q" value="{{ $searchQuery }}" placeholder="Name, username, or email">
                </label>
                <label>
                    <span>Status</span>
                    <select name="status">
                        @foreach ($filters as $filter)
                            <option value="{{ $filter['value'] }}" @selected($currentStatus === $filter['value'])>{{ $filter['label'] }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span>Country</span>
                    <select name="country">
                        <option value="all">Any country</option>
                        @foreach ($countries as $country)
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
                        <a href="{{ route('admin.seller-applications') }}">Clear</a>
                    @endif
                </div>
            </form>

            <div class="admin-filter-row">
                @foreach ($filters as $filter)
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
                <span>seller applications match the current status, country, activity, and search filters.</span>
            </div>

            @if ($canBulkReview)
                <form method="POST" action="{{ route('admin.seller-applications.bulk') }}" data-admin-seller-bulk-form>
                    @csrf
                    @method('PATCH')
                    <div class="admin-bulk-toolbar">
                        <label>
                            <span>Bulk actions</span>
                            <select name="bulk_action" required>
                                <option value="">Choose action</option>
                                <option value="approve">Approve selected</option>
                                <option value="reject">Reject selected</option>
                            </select>
                        </label>
                        <label>
                            <span>Review reason</span>
                            <input type="text" name="reason" value="{{ old('reason') }}" placeholder="Required when rejecting selected sellers">
                        </label>
                        <button type="submit">Apply</button>
                        <span data-admin-seller-selected-count>0 selected</span>
                    </div>
            @endif

            <div class="admin-table-wrap">
                <table class="admin-seller-table">
                    <thead>
                        <tr>
                            @if ($canBulkReview)
                                <th class="admin-table-select-cell">
                                    <input type="checkbox" data-admin-seller-select-all aria-label="Select all seller applications on this page">
                                </th>
                            @endif
                            <th>Seller</th>
                            <th>Status</th>
                            <th>Country</th>
                            <th>Reason</th>
                            <th>Reviewed</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sellers as $seller)
                            <tr>
                                @if ($canBulkReview)
                                    <td class="admin-table-select-cell">
                                        <input type="checkbox" name="sellers[]" value="{{ $seller->id }}" data-admin-seller-row-check aria-label="Select {{ $seller->name ?: $seller->email }}">
                                    </td>
                                @endif
                                <td>
                                    <a class="admin-user-table-name" href="{{ route('admin.seller-applications.show', $seller) }}">{{ $seller->name ?: $seller->email }}</a>
                                    <small>{{ '@'.$seller->username }} - {{ $seller->email }}</small>
                                </td>
                                <td><span class="admin-status-badge {{ $seller->seller_status === 'approved' ? 'is-good' : ($seller->seller_status === 'rejected' ? 'is-danger' : 'is-warn') }}">{{ str($seller->seller_status)->replace('_', ' ')->title() }}</span></td>
                                <td>{{ $seller->country ?: 'Unknown' }}</td>
                                <td>{{ str($seller->seller_status_reason ?: 'No reason recorded.')->limit(80) }}</td>
                                <td>{{ $seller->seller_status_reviewed_at?->format('M j, Y') ?? 'Not reviewed' }}</td>
                                <td><a class="admin-panel-link" href="{{ route('admin.seller-applications.show', $seller) }}">Open</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ $canBulkReview ? 7 : 6 }}">No seller applications matched this queue.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($canBulkReview)
                </form>
            @endif

            @include('admin.partials.pagination', ['pagination' => $pagination, 'label' => 'Seller application pagination'])
        </article>
    </section>
    @if ($canBulkReview)
        @include('admin.partials.bulk-select-scripts', [
            'form' => '[data-admin-seller-bulk-form]',
            'selectAll' => '[data-admin-seller-select-all]',
            'rowCheck' => '[data-admin-seller-row-check]',
            'selectedCount' => '[data-admin-seller-selected-count]',
            'itemName' => 'seller application',
        ])
    @endif
@endsection
