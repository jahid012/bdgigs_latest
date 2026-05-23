@extends('admin.layouts.panel')

@section('title', 'Dashboard')

@section('panel')
    <section class="admin-briefing-grid" aria-label="Daily operations briefing">
        <article class="admin-briefing-card">
            <div>
                <p class="admin-eyebrow">Today at a glance</p>
                <h2>Keep the marketplace moving without losing quality.</h2>
                <p>
                    Review the queues that affect buyer trust first: late orders, gig approvals, payout holds, and
                    urgent support cases.
                </p>
            </div>
            <div class="admin-briefing-metrics">
                @foreach ($briefing as $item)
                    <span>
                        <strong>{{ $item['value'] }}</strong>
                        <small>{{ $item['label'] }}</small>
                        <em>{{ $item['meta'] }}</em>
                    </span>
                @endforeach
            </div>
        </article>

        <article class="admin-health-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>System health</h2>
                    <p>Operational signals by workstream</p>
                </div>
            </div>
            <div class="admin-health-list">
                @foreach ($health as $item)
                    <span class="{{ $item['tone'] === 'good' ? 'is-good' : 'is-warn' }}">
                        <b>{{ $item['label'] }}</b>
                        <em>{{ $item['value'] }}</em>
                    </span>
                @endforeach
            </div>
        </article>
    </section>

    <section class="admin-stat-grid" aria-label="Marketplace stats">
        @foreach ($stats as $stat)
            <article class="admin-stat-card">
                <span>{{ $stat['label'] }}</span>
                <strong>{{ $stat['value'] }}</strong>
                <p>{{ $stat['meta'] }}</p>
            </article>
        @endforeach
    </section>

    <section class="admin-dashboard-grid">
        @include('admin.partials.line-chart', ['chart' => $revenueTrend])

        <article class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Moderation queue</h2>
                    <p>Items needing admin review</p>
                </div>
            </div>
            <div class="admin-queue-list">
                @foreach (($moderationQueue ?? []) as $item)
                    <a href="{{ route($item['route']) }}">
                        <span>{{ $item['label'] }}</span>
                        <strong>{{ $item['value'] }}</strong>
                    </a>
                @endforeach
            </div>
        </article>
    </section>

    <section class="admin-dashboard-grid lower">
        <article class="admin-panel admin-table-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Recent orders</h2>
                    <p>Track delivery risk and marketplace flow</p>
                </div>
                <a class="admin-panel-link" href="{{ route('admin.orders') }}">View all</a>
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
                    @forelse ($orders as $order)
                            <tr>
                                <td>{{ $order['id'] }}</td>
                                <td>{{ $order['buyer'] }}</td>
                                <td>{{ $order['seller'] }}</td>
                                <td>{{ $order['service'] }}</td>
                                <td><span>{{ $order['status'] }}</span></td>
                                <td>{{ $order['amount'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">No orders have been created yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('admin.partials.pagination', ['pagination' => $pagination, 'label' => 'Recent orders pagination'])
        </article>

        <article class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Activity</h2>
                    <p>Latest admin signals</p>
                </div>
            </div>
            <ol class="admin-activity-list">
                @foreach ($activities as $activity)
                    <li>{{ $activity }}</li>
                @endforeach
            </ol>
        </article>
    </section>

    <section class="admin-workflow-grid">
        <article class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Priority workflow</h2>
                    <p>Suggested admin sequence for today</p>
                </div>
            </div>
            <div class="admin-workflow-steps">
                @foreach (($priorityWorkflow ?? []) as $item)
                    <span><b>{{ $item['step'] }}</b><strong>{{ $item['label'] }}</strong><small>{{ $item['meta'] }}</small></span>
                @endforeach
            </div>
        </article>

        <article class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Marketplace quality</h2>
                    <p>Signals that need human judgment</p>
                </div>
            </div>
            <div class="admin-quality-bars">
                @foreach (($qualityBars ?? []) as $bar)
                    <span style="--value: {{ $bar['value'] }}%"><b>{{ $bar['label'] }}</b><em>{{ $bar['value'] }}%</em></span>
                @endforeach
            </div>
        </article>
    </section>
@endsection

@push('scripts')
    @include('admin.partials.line-chart-scripts')
@endpush
