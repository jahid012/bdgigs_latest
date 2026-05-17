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
            <button type="button">Export orders</button>
        </div>
        <div class="admin-filter-row">
            <button type="button" class="is-active">All</button>
            <button type="button">Late risk</button>
            <button type="button">Revision</button>
            <button type="button">Delivered</button>
            <button type="button">Cancelled</button>
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
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($orders as $order)
                        <tr>
                            <td>{{ $order['id'] }}</td>
                            <td>{{ $order['buyer'] }}</td>
                            <td>{{ $order['seller'] }}</td>
                            <td>{{ $order['service'] }}</td>
                            <td><span>{{ $order['status'] }}</span></td>
                            <td>{{ $order['amount'] }}</td>
                        </tr>
                    @endforeach
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
                <span style="--value: 92%"><b>On-time delivery</b><em>92%</em></span>
                <span style="--value: 63%"><b>Requirements completed</b><em>63%</em></span>
                <span style="--value: 18%"><b>Revision pressure</b><em>18%</em></span>
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
                <span><b>1</b><strong>Nudge missing requirements</strong><small>42 buyers</small></span>
                <span><b>2</b><strong>Contact late-risk sellers</strong><small>23 orders</small></span>
                <span><b>3</b><strong>Audit cancellation reasons</strong><small>5 orders</small></span>
            </div>
        </article>
    </section>
@endsection
