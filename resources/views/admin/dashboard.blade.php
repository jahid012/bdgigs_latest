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
        <article class="admin-panel admin-revenue-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Revenue trend</h2>
                    <p>Last 8 weeks marketplace performance</p>
                </div>
                <button type="button">Export</button>
            </div>
            <div class="admin-chart" aria-label="Revenue chart">
                @foreach ([48, 62, 54, 78, 70, 88, 76, 94] as $height)
                    <span style="height: {{ $height }}%"></span>
                @endforeach
            </div>
        </article>

        <article class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Moderation queue</h2>
                    <p>Items needing admin review</p>
                </div>
            </div>
            <div class="admin-queue-list">
                <a href="{{ route('admin.gigs') }}">
                    <span>Gig approvals</span>
                    <strong>74</strong>
                </a>
                <a href="{{ route('admin.users') }}">
                    <span>Seller documents</span>
                    <strong>18</strong>
                </a>
                <a href="{{ route('admin.disputes') }}">
                    <span>Reported messages</span>
                    <strong>6</strong>
                </a>
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
                <span><b>1</b><strong>Clear urgent disputes</strong><small>Protect buyer trust</small></span>
                <span><b>2</b><strong>Review gig backlog</strong><small>Unlock new seller inventory</small></span>
                <span><b>3</b><strong>Approve payout batch</strong><small>Keep sellers engaged</small></span>
                <span><b>4</b><strong>Check late orders</strong><small>Reduce cancellations</small></span>
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
                <span style="--value: 86%"><b>Gig image quality</b><em>86%</em></span>
                <span style="--value: 72%"><b>Requirement completion</b><em>72%</em></span>
                <span style="--value: 94%"><b>Seller response health</b><em>94%</em></span>
            </div>
        </article>
    </section>
@endsection
