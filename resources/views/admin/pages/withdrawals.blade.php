@extends('admin.layouts.panel')

@section('title', 'Withdrawals')

@section('panel')
    @include('admin.partials.stats', ['stats' => $stats])

    <section class="admin-page-grid">
        <article class="admin-panel admin-table-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Seller withdrawal queue</h2>
                    <p>Approve requests first, then record the manual payout reference when money is sent.</p>
                </div>
            </div>
            <form class="admin-user-search-form" method="GET" action="{{ route('admin.withdrawals') }}">
                <input type="hidden" name="status" value="{{ $currentFilter }}">
                <label>
                    <span>Withdrawal search</span>
                    <input type="search" name="q" value="{{ $searchQuery }}" placeholder="Search withdrawal, seller, or payment reference">
                </label>
                <button type="submit">Search withdrawals</button>
                @if ($searchQuery !== '' || $currentFilter !== 'pending')
                    <a href="{{ route('admin.withdrawals') }}">Clear</a>
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
                            <th>Withdrawal</th>
                            <th>Seller</th>
                            <th>Payout method</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Reference</th>
                            <th>Review</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($withdrawals as $withdrawal)
                            @php
                                $payout = $withdrawal->payout_snapshot ?? [];
                                $statusClass = match ($withdrawal->status) {
                                    'paid' => 'status-completed',
                                    'rejected', 'cancelled', 'failed' => 'status-cancelled',
                                    default => 'status-delivered',
                                };
                            @endphp
                            <tr>
                                <td>#{{ $withdrawal->code }}</td>
                                <td>{{ $withdrawal->seller?->name ?? 'Seller unavailable' }}</td>
                                <td>
                                    <strong>{{ $payout['label'] ?? 'Manual payout' }}</strong>
                                    <small>{{ $payout['accountNumber'] ?? 'No account reference' }}</small>
                                </td>
                                <td>${{ number_format(($withdrawal->approved_amount_cents ?: $withdrawal->amount_cents) / 100, 0) }}</td>
                                <td><span class="admin-status-badge {{ $statusClass }}">{{ str($withdrawal->status)->replace('_', ' ')->title() }}</span></td>
                                <td>{{ $withdrawal->payment_reference ?: 'Pending' }}</td>
                                <td>
                                    @if (in_array($withdrawal->status, ['pending', 'under_review'], true))
                                        @can('withdrawals.review')
                                            <form class="admin-inline-select-form" method="POST" action="{{ route('admin.withdrawals.review', $withdrawal) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="text" name="note" placeholder="Review note">
                                                <button type="submit" name="action" value="approve">Approve</button>
                                                <button type="submit" name="action" value="reject">Reject</button>
                                            </form>
                                        @endcan
                                    @elseif ($withdrawal->status === 'approved')
                                        <div class="admin-withdrawal-review-stack">
                                            @can('withdrawals.pay')
                                                <form class="admin-inline-select-form" method="POST" action="{{ route('admin.withdrawals.review', $withdrawal) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="text" name="payment_reference" placeholder="Payout reference" required>
                                                    <input type="text" name="note" placeholder="Payment note">
                                                    <button type="submit" name="action" value="mark_paid">Mark paid</button>
                                                </form>
                                            @endcan
                                            @can('withdrawals.review')
                                                <form class="admin-inline-select-form" method="POST" action="{{ route('admin.withdrawals.review', $withdrawal) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="text" name="note" placeholder="Reject note">
                                                    <button type="submit" name="action" value="reject">Reject</button>
                                                </form>
                                                <form class="admin-inline-select-form" method="POST" action="{{ route('admin.withdrawals.review', $withdrawal) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="text" name="note" placeholder="Failure note">
                                                    <button type="submit" name="action" value="mark_failed">Mark failed</button>
                                                </form>
                                            @endcan
                                        </div>
                                    @else
                                        {{ $withdrawal->payer?->name ?? $withdrawal->reviewer?->name ?? 'Closed' }}
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">No seller withdrawals matched this queue.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('admin.partials.pagination', ['pagination' => $pagination, 'label' => 'Withdrawal pagination'])
        </article>

        <aside class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Manual payout checklist</h2>
                    <p>Use the saved snapshot on each request before paying.</p>
                </div>
            </div>
            <ol class="admin-activity-list">
                <li>Confirm the seller account is active and the request has an approved amount.</li>
                <li>Send funds to the snapshotted payout method details, not an edited later value.</li>
                <li>Record the transfer reference before marking a withdrawal paid.</li>
            </ol>
        </aside>
    </section>
@endsection
