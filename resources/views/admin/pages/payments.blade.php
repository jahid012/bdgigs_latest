@extends('admin.layouts.panel')

@section('title', 'Payments')

@section('panel')
    @include('admin.partials.stats', ['stats' => $stats])

    <section class="admin-page-grid">
        <article class="admin-panel admin-table-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Payout queue</h2>
                    <p>Review seller payout batches and held transactions.</p>
                </div>
            </div>
            <div class="admin-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Payment</th>
                            <th>Seller</th>
                            <th>Method</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($payments as $payment)
                            <tr>
                                <td>{{ $payment['id'] }}</td>
                                <td>{{ $payment['seller'] }}</td>
                                <td>{{ $payment['method'] }}</td>
                                <td>{{ $payment['amount'] }}</td>
                                <td><span>{{ $payment['status'] }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">No delivered orders are ready for the future payout system.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('admin.partials.pagination', ['pagination' => $pagination, 'label' => 'Payments pagination'])
        </article>

        <aside class="admin-panel admin-finance-card">
            <div class="admin-panel-head">
                <div>
                    <h2>Finance controls</h2>
                    <p>Template controls for future payment workflows.</p>
                </div>
            </div>
            <button type="button" disabled>Hold payouts in Part 3</button>
            <button type="button" disabled>Release payouts in Part 3</button>
            <a class="admin-panel-link" href="{{ route('admin.reports') }}">Open finance report</a>
        </aside>
    </section>

    <section class="admin-workflow-grid">
        <article class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Payout readiness</h2>
                    <p>Finance queue grouped by release blockers.</p>
                </div>
            </div>
            <div class="admin-quality-bars">
                <span style="--value: 86%"><b>Cleared order funds</b><em>86%</em></span>
                <span style="--value: 74%"><b>Verified payout methods</b><em>74%</em></span>
                <span style="--value: 11%"><b>Manual holds</b><em>11%</em></span>
            </div>
        </article>

        <article class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Finance notes</h2>
                    <p>Items to review before release.</p>
                </div>
            </div>
            <ol class="admin-activity-list">
                <li>Two sellers changed bank account details this week.</li>
                <li>Refund reserve is within expected threshold.</li>
                <li>Eight transactions require manual hold confirmation.</li>
            </ol>
        </article>
    </section>
@endsection
