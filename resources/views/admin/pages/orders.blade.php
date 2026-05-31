@extends('admin.layouts.panel')

@section('title', 'Orders')

@section('panel')
    @include('admin.partials.stats', ['stats' => $stats])

    <article class="admin-panel admin-table-panel">
        <div class="admin-panel-head">
            <div>
                <h2>Order operations</h2>
                <p>Monitor delivery health, revision state, and value at risk.</p>
            </div>
        </div>
        <form class="admin-user-filter-form admin-order-filter-form" method="GET" action="{{ route('admin.orders') }}">
            <label>
                <span>Order search</span>
                <input type="search" name="q" value="{{ $searchQuery }}" placeholder="Search code, buyer, seller, service, or status">
            </label>
            <label>
                <span>Status</span>
                <select name="status">
                    @foreach ($filters as $filter)
                        <option value="{{ $filter['value'] }}" @selected($currentFilter === $filter['value'])>{{ $filter['label'] }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span>Buyer</span>
                <input type="search" name="buyer" value="{{ $filterState['buyer'] }}" placeholder="Buyer name or email">
            </label>
            <label>
                <span>Seller</span>
                <input type="search" name="seller" value="{{ $filterState['seller'] }}" placeholder="Seller name or email">
            </label>
            <label>
                <span>Payment</span>
                <select name="payment">
                    @foreach ($paymentFilters as $value => $label)
                        <option value="{{ $value }}" @selected($filterState['payment'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span>Due date</span>
                <select name="due">
                    @foreach ($dueFilters as $value => $label)
                        <option value="{{ $value }}" @selected($filterState['due'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span>Amount</span>
                <select name="amount">
                    @foreach ($amountFilters as $value => $label)
                        <option value="{{ $value }}" @selected($filterState['amount'] === $value)>{{ $label }}</option>
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
                    <a href="{{ route('admin.orders') }}">Clear</a>
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
            <span>orders match the current delivery, payment, buyer, seller, amount, and due-date filters.</span>
        </div>
        @can('orders.manage')
            <form method="POST" action="{{ route('admin.orders.bulk') }}" data-admin-order-bulk-form>
                @csrf
                @method('PATCH')
                <div class="admin-bulk-toolbar">
                    <label>
                        <span>Bulk actions</span>
                        <select name="status" required>
                            <option value="">Change status to...</option>
                            @foreach ($statusOptions as $status)
                                <option value="{{ $status }}">{{ $status }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span>Scope</span>
                        <input type="text" value="Selected orders only" disabled>
                    </label>
                    <button type="submit">Apply</button>
                    <span data-admin-order-selected-count>0 selected</span>
                </div>
        @endcan
        <div class="admin-table-wrap">
            <table class="admin-order-table">
                <thead>
                    <tr>
                        @can('orders.manage')
                            <th class="admin-table-select-cell">
                                <input type="checkbox" data-admin-order-select-all aria-label="Select all orders on this page">
                            </th>
                        @endcan
                        <th>Order</th>
                        <th>Buyer</th>
                        <th>Seller</th>
                        <th>Service</th>
                        <th>Status</th>
                        <th>Due</th>
                        <th>Amount</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            @can('orders.manage')
                                <td class="admin-table-select-cell">
                                    <input type="checkbox" name="orders[]" value="{{ $order['code'] }}" data-admin-order-row-check aria-label="Select {{ $order['id'] }}">
                                </td>
                            @endcan
                            <td><a class="admin-user-table-name" href="{{ route('admin.orders.show', $order['code']) }}">{{ $order['id'] }}</a></td>
                            <td>{{ $order['buyer'] }}</td>
                            <td>{{ $order['seller'] }}</td>
                            <td>{{ $order['service'] }}</td>
                            <td><span class="admin-status-badge {{ $order['status_class'] }}">{{ $order['status'] }}</span></td>
                            <td>{{ $order['due'] }}</td>
                            <td>{{ $order['amount'] }}</td>
                            <td>
                                <a class="admin-panel-link" href="{{ route('admin.orders.show', $order['code']) }}">View details</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth('admin')->user()?->can('orders.manage') ? 9 : 8 }}">No orders matched your filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @can('orders.manage')
            </form>
        @endcan
        @include('admin.partials.pagination', ['pagination' => $pagination, 'label' => 'Orders pagination'])
    </article>

    <section class="admin-workflow-grid">
        <article class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Delivery SLA</h2>
                    <p>Orders grouped by delivery risk.</p>
                </div>
            </div>
            <div class="admin-quality-bars">
                @foreach ($slaBars as $bar)
                    <span style="--value: {{ $bar['value'] }}%"><b>{{ $bar['label'] }}</b><em>{{ $bar['value'] }}%</em></span>
                @endforeach
            </div>
        </article>

        <article class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Admin actions</h2>
                    <p>Reduce late delivery and cancellation risk.</p>
                </div>
            </div>
            <div class="admin-workflow-steps">
                @foreach ($workflowSteps as $item)
                    <span><b>{{ $item['step'] }}</b><strong>{{ $item['label'] }}</strong><small>{{ $item['meta'] }}</small></span>
                @endforeach
            </div>
        </article>
    </section>
    @can('orders.manage')
        @include('admin.partials.bulk-select-scripts', [
            'form' => '[data-admin-order-bulk-form]',
            'selectAll' => '[data-admin-order-select-all]',
            'rowCheck' => '[data-admin-order-row-check]',
            'selectedCount' => '[data-admin-order-selected-count]',
            'itemName' => 'order',
        ])
    @endcan
@endsection
