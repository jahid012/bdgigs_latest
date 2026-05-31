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
            <form class="admin-user-filter-form admin-payment-filter-form" method="GET" action="{{ route('admin.manual-payments') }}">
                <label>
                    <span>Payment search</span>
                    <input type="search" name="q" value="{{ $searchQuery }}" placeholder="Search order, buyer, or reference">
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
                    <span>Method</span>
                    <select name="method">
                        <option value="all">Any method</option>
                        @foreach ($methods as $method)
                            <option value="{{ $method->id }}" @selected((string) $filterState['method'] === (string) $method->id)>{{ $method->name }}</option>
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
                        <a href="{{ route('admin.manual-payments') }}">Clear</a>
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
                <span>manual payment submissions match the current status, method, amount, and search filters.</span>
            </div>
            @if ($canBulkReview)
                <form method="POST" action="{{ route('admin.manual-payments.bulk') }}" data-admin-payment-bulk-form>
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
                            <span>Review note</span>
                            <input type="text" name="note" value="{{ old('note') }}" placeholder="Optional note for selected payments">
                        </label>
                        <button type="submit">Apply</button>
                        <span data-admin-payment-selected-count>0 selected</span>
                    </div>
            @endif
            <div class="admin-table-wrap">
                <table class="admin-payment-table">
                    <thead>
                        <tr>
                            @if ($canBulkReview)
                                <th class="admin-table-select-cell">
                                    <input type="checkbox" data-admin-payment-select-all aria-label="Select all manual payments on this page">
                                </th>
                            @endif
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
                                @if ($canBulkReview)
                                    <td class="admin-table-select-cell">
                                        @if ($submission->status === 'pending')
                                            <input type="checkbox" name="submissions[]" value="{{ $submission->id }}" data-admin-payment-row-check aria-label="Select payment {{ $submission->reference }}">
                                        @else
                                            <span class="sr-only">Reviewed payment cannot be selected</span>
                                        @endif
                                    </td>
                                @endif
                                <td>
                                    @if ($submission->order)
                                        <a class="admin-user-table-name" href="{{ route('admin.orders.show', $submission->order->code) }}">#{{ $submission->order->code }}</a>
                                    @else
                                        #Unavailable
                                    @endif
                                </td>
                                <td>{{ $submission->buyer?->name ?? 'Buyer unavailable' }}</td>
                                <td>{{ $submission->method?->name ?? 'Method unavailable' }}</td>
                                <td>{{ $submission->reference }}</td>
                                <td>${{ number_format($submission->amount_cents / 100, 0) }}</td>
                                <td><span class="admin-status-badge {{ $submission->status === 'approved' ? 'status-completed' : ($submission->status === 'rejected' ? 'status-cancelled' : 'status-delivered') }}">{{ str($submission->status)->title() }}</span></td>
                                <td>
                                    @if ($submission->status === 'pending')
                                        @can('manual-payments.approve')
                                            <button class="admin-inline-action-button" type="button" data-admin-modal-open="payment-review-{{ $submission->id }}">Review</button>
                                        @endcan
                                    @else
                                        {{ $submission->adminReviewer?->name ?? $submission->reviewer?->name ?? 'Reviewed' }}
                                    @endif
                                </td>
                            </tr>
                    @empty
                        <tr>
                                <td colspan="{{ $canBulkReview ? 8 : 7 }}">No manual payment submissions matched this queue.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if ($canBulkReview)
                </form>
            @endif
            @can('manual-payments.approve')
                @foreach ($submissions as $submission)
                    @if ($submission->status === 'pending')
                        <dialog class="admin-modal" id="payment-review-{{ $submission->id }}" data-admin-modal>
                            <div class="admin-modal-panel">
                                <div class="admin-modal-head">
                                    <div>
                                        <p class="admin-eyebrow">Manual payment</p>
                                        <h2>Review {{ $submission->reference }}</h2>
                                        <span>{{ $submission->buyer?->name ?? 'Buyer unavailable' }} submitted ${{ number_format($submission->amount_cents / 100, 2) }} for order #{{ $submission->order?->code ?? 'unavailable' }}.</span>
                                    </div>
                                    <button type="button" data-admin-modal-close aria-label="Close payment review modal">Close</button>
                                </div>
                                <form class="admin-detail-form admin-modal-form" method="POST" action="{{ route('admin.manual-payments.review', $submission) }}">
                                    @csrf
                                    @method('PATCH')
                                    <label>
                                        <span>Review note</span>
                                        <textarea name="note" rows="4" placeholder="Reference checked, mismatch found, or buyer follow-up needed"></textarea>
                                    </label>
                                    <div class="admin-modal-actions">
                                        <button type="button" class="admin-secondary-button" data-admin-modal-close>Cancel</button>
                                        <button type="submit" name="decision" value="approve">Approve payment</button>
                                        <button class="is-danger" type="submit" name="decision" value="reject">Reject payment</button>
                                    </div>
                                </form>
                            </div>
                        </dialog>
                    @endif
                @endforeach
            @endcan
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
    @if ($canBulkReview)
        @include('admin.partials.bulk-select-scripts', [
            'form' => '[data-admin-payment-bulk-form]',
            'selectAll' => '[data-admin-payment-select-all]',
            'rowCheck' => '[data-admin-payment-row-check]',
            'selectedCount' => '[data-admin-payment-selected-count]',
            'itemName' => 'manual payment',
        ])
    @endif
    @include('admin.partials.modal-scripts')
@endsection
