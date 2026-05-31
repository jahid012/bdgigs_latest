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
            <form class="admin-user-filter-form admin-withdrawal-filter-form" method="GET" action="{{ route('admin.withdrawals') }}">
                <label>
                    <span>Withdrawal search</span>
                    <input type="search" name="q" value="{{ $searchQuery }}" placeholder="Search withdrawal, seller, or payment reference">
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
                    <span>Seller</span>
                    <input type="search" name="seller" value="{{ $filterState['seller'] }}" placeholder="Seller name, email, or username">
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
                        <a href="{{ route('admin.withdrawals') }}">Clear</a>
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
                <span>withdrawal requests match the current status, seller, amount, and search filters.</span>
            </div>
            @if ($canBulkReview)
                <form method="POST" action="{{ route('admin.withdrawals.bulk') }}" data-admin-withdrawal-bulk-form>
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
                            <input type="text" name="note" value="{{ old('note') }}" placeholder="Optional note for selected withdrawals">
                        </label>
                        <button type="submit">Apply</button>
                        <span data-admin-withdrawal-selected-count>0 selected</span>
                    </div>
            @endif
            <div class="admin-table-wrap">
                <table class="admin-withdrawal-table">
                    <thead>
                        <tr>
                            @if ($canBulkReview)
                                <th class="admin-table-select-cell">
                                    <input type="checkbox" data-admin-withdrawal-select-all aria-label="Select all withdrawals on this page">
                                </th>
                            @endif
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
                                @if ($canBulkReview)
                                    <td class="admin-table-select-cell">
                                        @if (in_array($withdrawal->status, ['pending', 'under_review', 'approved'], true))
                                            <input type="checkbox" name="withdrawals[]" value="{{ $withdrawal->code }}" data-admin-withdrawal-row-check aria-label="Select withdrawal {{ $withdrawal->code }}">
                                        @else
                                            <span class="sr-only">Closed withdrawal cannot be selected</span>
                                        @endif
                                    </td>
                                @endif
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
                                            <button class="admin-inline-action-button" type="button" data-admin-modal-open="withdrawal-review-{{ $withdrawal->id }}">Review</button>
                                        @endcan
                                    @elseif ($withdrawal->status === 'approved')
                                        @can('withdrawals.pay')
                                            <button class="admin-inline-action-button" type="button" data-admin-modal-open="withdrawal-pay-{{ $withdrawal->id }}">Mark paid</button>
                                        @endcan
                                        @can('withdrawals.review')
                                            <button class="admin-inline-action-button" type="button" data-admin-modal-open="withdrawal-close-{{ $withdrawal->id }}">Close</button>
                                        @endcan
                                    @else
                                        {{ $withdrawal->adminPayer?->name ?? $withdrawal->payer?->name ?? $withdrawal->adminReviewer?->name ?? $withdrawal->reviewer?->name ?? 'Closed' }}
                                    @endif
                                </td>
                            </tr>
                    @empty
                        <tr>
                                <td colspan="{{ $canBulkReview ? 8 : 7 }}">No seller withdrawals matched this queue.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if ($canBulkReview)
                </form>
            @endif
            @foreach ($withdrawals as $withdrawal)
                @if (in_array($withdrawal->status, ['pending', 'under_review'], true))
                    @can('withdrawals.review')
                        <dialog class="admin-modal" id="withdrawal-review-{{ $withdrawal->id }}" data-admin-modal>
                            <div class="admin-modal-panel">
                                <div class="admin-modal-head">
                                    <div>
                                        <p class="admin-eyebrow">Withdrawal review</p>
                                        <h2>Review #{{ $withdrawal->code }}</h2>
                                        <span>{{ $withdrawal->seller?->name ?? 'Seller unavailable' }} requested ${{ number_format(($withdrawal->approved_amount_cents ?: $withdrawal->amount_cents) / 100, 2) }}.</span>
                                    </div>
                                    <button type="button" data-admin-modal-close aria-label="Close withdrawal review modal">Close</button>
                                </div>
                                <form class="admin-detail-form admin-modal-form" method="POST" action="{{ route('admin.withdrawals.review', $withdrawal) }}">
                                    @csrf
                                    @method('PATCH')
                                    <label>
                                        <span>Review note</span>
                                        <textarea name="note" rows="4" placeholder="Payout details checked, mismatch found, or seller follow-up needed"></textarea>
                                    </label>
                                    <div class="admin-modal-actions">
                                        <button type="button" class="admin-secondary-button" data-admin-modal-close>Cancel</button>
                                        <button type="submit" name="action" value="approve">Approve withdrawal</button>
                                        <button class="is-danger" type="submit" name="action" value="reject">Reject withdrawal</button>
                                    </div>
                                </form>
                            </div>
                        </dialog>
                    @endcan
                @elseif ($withdrawal->status === 'approved')
                    @can('withdrawals.pay')
                        <dialog class="admin-modal" id="withdrawal-pay-{{ $withdrawal->id }}" data-admin-modal>
                            <div class="admin-modal-panel">
                                <div class="admin-modal-head">
                                    <div>
                                        <p class="admin-eyebrow">Manual payout</p>
                                        <h2>Mark #{{ $withdrawal->code }} paid</h2>
                                        <span>Record the transfer reference after sending funds to the snapshotted payout details.</span>
                                    </div>
                                    <button type="button" data-admin-modal-close aria-label="Close payout modal">Close</button>
                                </div>
                                <form class="admin-detail-form admin-modal-form" method="POST" action="{{ route('admin.withdrawals.review', $withdrawal) }}">
                                    @csrf
                                    @method('PATCH')
                                    <label>
                                        <span>Payout reference</span>
                                        <input type="text" name="payment_reference" required placeholder="Bank, wallet, or transfer reference">
                                    </label>
                                    <label>
                                        <span>Payment note</span>
                                        <textarea name="note" rows="4" placeholder="Optional internal payout note"></textarea>
                                    </label>
                                    <div class="admin-modal-actions">
                                        <button type="button" class="admin-secondary-button" data-admin-modal-close>Cancel</button>
                                        <button type="submit" name="action" value="mark_paid">Mark paid</button>
                                    </div>
                                </form>
                            </div>
                        </dialog>
                    @endcan
                    @can('withdrawals.review')
                        <dialog class="admin-modal" id="withdrawal-close-{{ $withdrawal->id }}" data-admin-modal>
                            <div class="admin-modal-panel">
                                <div class="admin-modal-head">
                                    <div>
                                        <p class="admin-eyebrow">Withdrawal closeout</p>
                                        <h2>Close #{{ $withdrawal->code }}</h2>
                                        <span>Reject it before payout or mark the approved payout as failed.</span>
                                    </div>
                                    <button type="button" data-admin-modal-close aria-label="Close withdrawal closeout modal">Close</button>
                                </div>
                                <form class="admin-detail-form admin-modal-form" method="POST" action="{{ route('admin.withdrawals.review', $withdrawal) }}">
                                    @csrf
                                    @method('PATCH')
                                    <label>
                                        <span>Closeout note</span>
                                        <textarea name="note" rows="4" placeholder="Explain the rejection or payment failure"></textarea>
                                    </label>
                                    <div class="admin-modal-actions">
                                        <button type="button" class="admin-secondary-button" data-admin-modal-close>Cancel</button>
                                        <button class="is-danger" type="submit" name="action" value="reject">Reject</button>
                                        <button class="is-danger" type="submit" name="action" value="mark_failed">Mark failed</button>
                                    </div>
                                </form>
                            </div>
                        </dialog>
                    @endcan
                @endif
            @endforeach
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
    @if ($canBulkReview)
        @include('admin.partials.bulk-select-scripts', [
            'form' => '[data-admin-withdrawal-bulk-form]',
            'selectAll' => '[data-admin-withdrawal-select-all]',
            'rowCheck' => '[data-admin-withdrawal-row-check]',
            'selectedCount' => '[data-admin-withdrawal-selected-count]',
            'itemName' => 'withdrawal',
        ])
    @endif
    @include('admin.partials.modal-scripts')
@endsection
