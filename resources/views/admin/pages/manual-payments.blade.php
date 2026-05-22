@extends('admin.layouts.panel')

@section('title', 'Manual Payments')

@section('panel')
    @include('admin.partials.stats', ['stats' => $stats])

    <section class="admin-page-grid">
        <article class="admin-panel admin-table-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Buyer payment review</h2>
                    <p>Approve or reject submitted manual payment references before delivery starts.</p>
                </div>
            </div>
            <form class="admin-user-search-form" method="GET" action="{{ route('admin.manual-payments') }}">
                <input type="hidden" name="status" value="{{ $currentFilter }}">
                <label>
                    <span>Payment search</span>
                    <input type="search" name="q" value="{{ $searchQuery }}" placeholder="Search order, buyer, or reference">
                </label>
                <button type="submit">Search payments</button>
                @if ($searchQuery !== '' || $currentFilter !== 'pending')
                    <a href="{{ route('admin.manual-payments') }}">Clear</a>
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
                            <th>Method</th>
                            <th>Reference</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Review</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($submissions as $submission)
                            <tr>
                                <td>#{{ $submission->order?->code }}</td>
                                <td>{{ $submission->buyer?->name ?? 'Buyer unavailable' }}</td>
                                <td>{{ $submission->method?->name ?? 'Method unavailable' }}</td>
                                <td>{{ $submission->reference }}</td>
                                <td>${{ number_format($submission->amount_cents / 100, 0) }}</td>
                                <td><span class="admin-status-badge {{ $submission->status === 'approved' ? 'status-completed' : ($submission->status === 'rejected' ? 'status-cancelled' : 'status-delivered') }}">{{ str($submission->status)->title() }}</span></td>
                                <td>
                                    @if ($submission->status === 'pending')
                                        @can('manual-payments.approve')
                                            <form class="admin-inline-select-form" method="POST" action="{{ route('admin.manual-payments.review', $submission) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="text" name="note" placeholder="Review note">
                                                <button type="submit" name="decision" value="approve">Approve</button>
                                                <button type="submit" name="decision" value="reject">Reject</button>
                                            </form>
                                        @endcan
                                    @else
                                        {{ $submission->reviewer?->name ?? 'Reviewed' }}
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">No manual payment submissions matched this queue.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('admin.partials.pagination', ['pagination' => $pagination, 'label' => 'Manual payment pagination'])
        </article>

        <aside class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Active payment methods</h2>
                    <p>Checkout shows only active manual methods.</p>
                </div>
            </div>
            <div class="admin-card-list compact">
                @forelse ($methods as $method)
                    <article class="admin-mini-card">
                        <div>
                            <strong>{{ $method->name }}</strong>
                            <p>{{ $method->account_name }} {{ $method->account_number }}</p>
                        </div>
                        <b>{{ $method->active ? 'Active' : 'Hidden' }}</b>
                    </article>
                @empty
                    <p class="admin-empty-note">Seed or add a manual payment method before checkout testing.</p>
                @endforelse
            </div>
        </aside>
    </section>
@endsection
