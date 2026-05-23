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
        <form class="admin-user-search-form" method="GET" action="{{ route('admin.orders') }}">
            <input type="hidden" name="status" value="{{ $currentFilter }}">
            <label>
                <span>Order search</span>
                <input type="search" name="q" value="{{ $searchQuery }}" placeholder="Search code, buyer, seller, service, or status">
            </label>
            <button type="submit">Search orders</button>
            @if ($searchQuery !== '' || $currentFilter !== 'all')
                <a href="{{ route('admin.orders') }}">Clear</a>
            @endif
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
        <div class="admin-table-wrap">
            <table>
                <thead>
                    <tr>
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
                            <td>{{ $order['id'] }}</td>
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
                            <td colspan="8">No orders matched your filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
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
@endsection
