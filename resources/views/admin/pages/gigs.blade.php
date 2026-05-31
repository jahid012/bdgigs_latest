@extends('admin.layouts.panel')

@section('title', 'Gigs')

@section('panel')
    @php
        $canBulkModerate = auth('admin')->user()?->can('gigs.review') || auth('admin')->user()?->can('gigs.publish');
    @endphp

    @include('admin.partials.stats', ['stats' => $stats])

    <article class="admin-panel admin-table-panel">
        <div class="admin-panel-head">
            <div>
                <h2>Gig catalog</h2>
                <p>Filter, inspect, and apply moderation changes to selected services.</p>
            </div>
        </div>

        <form class="admin-user-filter-form admin-gig-filter-form" method="GET" action="{{ route('admin.gigs') }}">
            <label>
                <span>Gig search</span>
                <input type="search" name="q" value="{{ $searchQuery }}" placeholder="Search title, seller, category, or status">
            </label>
            <label>
                <span>Status</span>
                <select name="status">
                    @foreach ($filters as $filter)
                        <option value="{{ $filter['value'] }}" @selected($currentFilter === $filter['value'])>
                            {{ $filter['label'] }} ({{ number_format($filter['count']) }})
                        </option>
                    @endforeach
                </select>
            </label>
            <label>
                <span>Category</span>
                <select name="category">
                    <option value="">All categories</option>
                    @foreach ($categoryOptions as $category)
                        <option value="{{ $category }}" @selected($filterState['category'] === $category)>{{ $category }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span>Seller</span>
                <input type="search" name="seller" value="{{ $filterState['seller'] }}" placeholder="Name, username, or email">
            </label>
            <label>
                <span>Featured</span>
                <select name="featured">
                    @foreach ($featuredFilters as $value => $label)
                        <option value="{{ $value }}" @selected($filterState['featured'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span>Price</span>
                <select name="price">
                    @foreach ($priceFilters as $value => $label)
                        <option value="{{ $value }}" @selected($filterState['price'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span>Delivery</span>
                <select name="delivery">
                    @foreach ($deliveryFilters as $value => $label)
                        <option value="{{ $value }}" @selected($filterState['delivery'] === $value)>{{ $label }}</option>
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
                    <a href="{{ route('admin.gigs') }}">Clear</a>
                @endif
            </div>
        </form>

        <div class="admin-filter-row">
            @foreach ($filters as $filter)
                <a
                    class="{{ $currentFilter === $filter['value'] ? 'is-active' : '' }}"
                    href="{{ request()->fullUrlWithQuery(['status' => $filter['value'], 'page' => 1]) }}"
                >
                    {{ $filter['label'] }} <span>{{ number_format($filter['count']) }}</span>
                </a>
            @endforeach
        </div>

        <div class="admin-filter-summary">
            <strong>{{ number_format($pagination['total']) }}</strong>
            <span>gigs match the current catalog filters.</span>
        </div>

        @if ($canBulkModerate)
            <form method="POST" action="{{ route('admin.gigs.bulk') }}" data-admin-gig-bulk-form>
                @csrf
                @method('PATCH')

                <div class="admin-bulk-toolbar">
                    <label>
                        <span>Bulk actions</span>
                        <select name="bulk_action" required>
                            <option value="">Choose action</option>
                            @can('gigs.publish')
                                <option value="approve">Approve</option>
                                <option value="pause">Pause</option>
                                <option value="deactivate">Deactivate</option>
                                <option value="reactivate">Reactivate</option>
                                <option value="feature">Feature</option>
                                <option value="unfeature">Remove featured</option>
                            @endcan
                            @can('gigs.review')
                                <option value="request_edits">Request edits</option>
                                <option value="reject">Reject</option>
                            @endcan
                        </select>
                    </label>
                    <label>
                        <span>Moderation note</span>
                        <input type="text" name="reason" value="{{ old('reason') }}" placeholder="Required for reject, request edits, deactivate">
                    </label>
                    <button type="submit">Apply</button>
                    <span data-admin-gig-selected-count>0 selected</span>
                </div>
        @endif

        <div class="admin-table-wrap">
            <table class="admin-gig-table">
                <thead>
                    <tr>
                        @if ($canBulkModerate)
                            <th class="admin-table-select-cell">
                                <input type="checkbox" data-admin-gig-select-all aria-label="Select all gigs on this page">
                            </th>
                        @endif
                        <th>Gig</th>
                        <th>Seller</th>
                        <th>Status</th>
                        <th>Featured</th>
                        <th>Price</th>
                        <th>Delivery</th>
                        <th>Rating</th>
                        <th>Updated</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($gigs as $gig)
                        <tr>
                            @if ($canBulkModerate)
                                <td class="admin-table-select-cell">
                                    @if (! $gig['deleted'])
                                        <input type="checkbox" name="gigs[]" value="{{ $gig['id'] }}" data-admin-gig-row-check aria-label="Select {{ $gig['title'] }}">
                                    @else
                                        <span class="sr-only">Deleted gig cannot be selected</span>
                                    @endif
                                </td>
                            @endif
                            <td>
                                <a class="admin-user-table-name" href="{{ route('admin.gigs.show', $gig['id']) }}">{{ $gig['title'] }}</a>
                                <small>{{ $gig['category'] }}{{ $gig['deleted'] ? ' - soft deleted' : '' }}</small>
                            </td>
                            <td>
                                {{ $gig['seller'] }}
                                @if ($gig['seller_email'])
                                    <small>{{ $gig['seller_email'] }}</small>
                                @endif
                            </td>
                            <td><span class="admin-status-badge {{ $gig['status_class'] }}">{{ $gig['deleted'] ? 'Deleted' : $gig['status'] }}</span></td>
                            <td>
                                <span class="admin-status-badge {{ $gig['featured'] ? 'status-completed' : 'status-delivered' }}">
                                    {{ $gig['featured'] ? 'Featured' : 'No' }}
                                </span>
                            </td>
                            <td>{{ $gig['price'] }}</td>
                            <td>{{ $gig['delivery'] }}</td>
                            <td>
                                {{ $gig['rating'] }}
                                <small>{{ $gig['reviews'] }} reviews</small>
                            </td>
                            <td>{{ $gig['updated'] }}</td>
                            <td>
                                <a class="admin-panel-link" href="{{ route('admin.gigs.show', $gig['id']) }}">View details</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canBulkModerate ? 10 : 9 }}">No gigs matched your filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($canBulkModerate)
            </form>
        @endif

        @include('admin.partials.pagination', ['pagination' => $pagination, 'label' => 'Gig catalog pagination'])
    </article>

    <section class="admin-workflow-grid">
        <article class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Category health</h2>
                    <p>Inventory quality by top marketplace category.</p>
                </div>
            </div>
            <div class="admin-quality-bars">
                @forelse ($categoryHealth as $category)
                    <span style="--value: {{ $category['value'] }}%"><b>{{ $category['label'] }}</b><em>{{ $category['value'] }}%</em></span>
                @empty
                    <span style="--value: 0%"><b>No categories yet</b><em>0%</em></span>
                @endforelse
            </div>
        </article>

        <article class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Common rejection reasons</h2>
                    <p>Use these signals to improve seller guidance.</p>
                </div>
            </div>
            <div class="admin-card-list compact">
                @foreach ($rejectionReasons as $reason)
                    <article class="admin-mini-card"><div><strong>{{ $reason['label'] }}</strong><p>{{ $reason['meta'] }}</p></div><b>{{ $reason['tone'] }}</b></article>
                @endforeach
            </div>
        </article>
    </section>

    @if ($canBulkModerate)
        <script>
            document.addEventListener("DOMContentLoaded", () => {
                const form = document.querySelector("[data-admin-gig-bulk-form]");

                if (! form) {
                    return;
                }

                const selectAll = form.querySelector("[data-admin-gig-select-all]");
                const rowChecks = Array.from(form.querySelectorAll("[data-admin-gig-row-check]"));
                const selectedCount = form.querySelector("[data-admin-gig-selected-count]");
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
                            window.notify.error("Select at least one gig before applying a bulk action.");
                        } else {
                            window.alert("Select at least one gig before applying a bulk action.");
                        }
                    }
                });

                updateSelectionState();
            });
        </script>
    @endif
@endsection
