@extends('admin.layouts.panel')

@section('title', 'Order Details')

@section('panel')
    @include('admin.partials.stats', ['stats' => $stats])

    @php
        $metadata = $order->metadata ?? [];
        $requirements = collect($metadata['requirements'] ?? []);
        $payment = $order->manualPaymentSubmission;
    @endphp

    <section class="admin-detail-layout">
        <article class="admin-panel admin-detail-summary">
            <div class="admin-detail-head admin-order-detail-head">
                <div>
                    <div class="admin-detail-badges">
                        <span class="admin-status-badge {{ $order->status_class }}">{{ $order->status }}</span>
                        @if ($payment)
                            <span class="admin-status-badge {{ $payment->status === 'approved' ? 'status-completed' : ($payment->status === 'rejected' ? 'status-cancelled' : 'status-delivered') }}">
                                Payment {{ str($payment->status)->title() }}
                            </span>
                        @endif
                    </div>
                    <h2>#{{ $order->code }} - {{ $order->service }}</h2>
                    <p>{{ $order->buyer?->name ?: $order->buyer_name ?: 'Buyer unavailable' }} buying from {{ $order->seller?->name ?: $order->seller_name ?: 'Seller unavailable' }}</p>
                </div>
            </div>

            <dl class="admin-user-detail-list admin-detail-list">
                <div><dt>Buyer</dt><dd>{{ $order->buyer?->email ?: $order->buyer_name ?: 'Unavailable' }}</dd></div>
                <div><dt>Seller</dt><dd>{{ $order->seller?->email ?: $order->seller_name ?: 'Unavailable' }}</dd></div>
                <div><dt>Gig</dt><dd>{{ $order->gig?->slug ?: 'No linked gig' }}</dd></div>
                <div><dt>Payment status</dt><dd>{{ str($order->payment_status ?: 'unpaid')->replace('_', ' ')->title() }}</dd></div>
                <div><dt>Transaction</dt><dd>{{ $order->transaction_id ?: 'No transaction recorded' }}</dd></div>
                <div><dt>Created</dt><dd>{{ $order->created_at?->format('M j, Y g:i A') ?? 'Unknown' }}</dd></div>
                <div><dt>Due</dt><dd>{{ $order->due_date?->format('M j, Y') ?? 'No due date' }}</dd></div>
                <div><dt>Quantity</dt><dd>{{ $metadata['quantity'] ?? 1 }}</dd></div>
            </dl>

            <div class="admin-user-detail-actions">
                <a href="{{ route('admin.orders') }}">Back to orders</a>
                @if ($order->gig)
                    <a href="{{ route('admin.gigs.show', $order->gig) }}">View gig</a>
                @endif
                @if ($order->buyer)
                    <a href="{{ route('admin.users.show', $order->buyer) }}">View buyer</a>
                @endif
                @if ($order->seller)
                    <a href="{{ route('admin.users.show', $order->seller) }}">View seller</a>
                @endif
            </div>
        </article>

        <aside class="admin-panel admin-detail-action-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Order action</h2>
                    <p>Open an action, review the impact, then confirm.</p>
                </div>
            </div>
            <div class="admin-moderation-summary">
                <span><strong>{{ $order->status }}</strong>Current status</span>
                <span><strong>{{ str($order->payment_status ?: 'unpaid')->replace('_', ' ')->title() }}</strong>Payment state</span>
            </div>
            <div class="admin-moderation-action-list">
            @can('orders.manage')
                <button class="admin-moderation-action-button is-positive" type="button" data-admin-modal-open="order-status-modal">
                    <strong>Update status</strong>
                    <span>Move the order through delivery, revision, completion, or cancellation states.</span>
                </button>
            @else
                <p class="admin-empty-note">You can inspect this order but cannot change its status.</p>
            @endcan

            @if (in_array($order->payment_status, ['paid', 'partially_refunded'], true))
                @canany(['payments.release', 'orders.manage'])
                    <button class="admin-moderation-action-button is-warning" type="button" data-admin-modal-open="order-refund-modal">
                        <strong>Refund order</strong>
                        <span>Issue a partial or full refund and record the reason in payment history.</span>
                    </button>
                @endcanany
            @endif

            @can('orders.manage')
                @if (! in_array($order->status, ['Completed', 'Cancelled', 'Canceled'], true))
                    <button class="admin-moderation-action-button is-danger" type="button" data-admin-modal-open="order-cancel-modal">
                        <strong>Cancel order</strong>
                        <span>Cancel the order through support and keep the reason visible to the audit trail.</span>
                    </button>
                @endif
            @endcan
            </div>
        </aside>
    </section>

    @can('orders.manage')
        <dialog class="admin-modal" id="order-status-modal" data-admin-modal>
            <div class="admin-modal-panel">
                <div class="admin-modal-head">
                    <div>
                        <p class="admin-eyebrow">Order status</p>
                        <h2>Update order status</h2>
                        <span>Status changes are recorded in the order activity log.</span>
                    </div>
                    <button type="button" data-admin-modal-close aria-label="Close status modal">Close</button>
                </div>
                <form class="admin-detail-form admin-modal-form" method="POST" action="{{ route('admin.orders.status', $order) }}">
                    @csrf
                    @method('PATCH')
                    <label>
                        <span>Status</span>
                        <select name="status">
                            @foreach ($statusOptions as $status)
                                <option value="{{ $status }}" @selected($order->status === $status)>{{ $status }}</option>
                            @endforeach
                        </select>
                    </label>
                    <div class="admin-modal-actions">
                        <button type="button" class="admin-secondary-button" data-admin-modal-close>Cancel</button>
                        <button type="submit">Save order status</button>
                    </div>
                </form>
            </div>
        </dialog>

        @if (! in_array($order->status, ['Completed', 'Cancelled', 'Canceled'], true))
            <dialog class="admin-modal" id="order-cancel-modal" data-admin-modal>
                <div class="admin-modal-panel">
                    <div class="admin-modal-head">
                        <div>
                            <p class="admin-eyebrow">Order cancellation</p>
                            <h2>Cancel this order</h2>
                            <span>Use this only when support has enough context to close the delivery workflow.</span>
                        </div>
                        <button type="button" data-admin-modal-close aria-label="Close cancellation modal">Close</button>
                    </div>
                    <form class="admin-detail-form admin-modal-form" method="POST" action="{{ route('admin.orders.cancel', $order) }}">
                        @csrf
                        <label>
                            <span>Admin cancellation reason</span>
                            <textarea name="reason" rows="4" required minlength="10" placeholder="Explain why support is cancelling this order"></textarea>
                        </label>
                        <div class="admin-modal-actions">
                            <button type="button" class="admin-secondary-button" data-admin-modal-close>Cancel</button>
                            <button class="is-danger" type="submit">Cancel order</button>
                        </div>
                    </form>
                </div>
            </dialog>
        @endif
    @endcan

    @if (in_array($order->payment_status, ['paid', 'partially_refunded'], true))
        @canany(['payments.release', 'orders.manage'])
            <dialog class="admin-modal" id="order-refund-modal" data-admin-modal>
                <div class="admin-modal-panel">
                    <div class="admin-modal-head">
                        <div>
                            <p class="admin-eyebrow">Payment refund</p>
                            <h2>Refund order #{{ $order->code }}</h2>
                            <span>Refundable balance: ${{ number_format(max(0, ($order->price_cents - $order->refund_amount_cents) / 100), 2) }}.</span>
                        </div>
                        <button type="button" data-admin-modal-close aria-label="Close refund modal">Close</button>
                    </div>
                    <form class="admin-detail-form admin-modal-form" method="POST" action="{{ route('admin.orders.refund', $order) }}">
                        @csrf
                        <label>
                            <span>Refund amount</span>
                            <input name="amount" type="number" min="0.01" step="0.01" max="{{ max(0, ($order->price_cents - $order->refund_amount_cents) / 100) }}" placeholder="{{ number_format(max(0, ($order->price_cents - $order->refund_amount_cents) / 100), 2) }}">
                        </label>
                        <label>
                            <span>Refund reason</span>
                            <textarea name="reason" rows="4" placeholder="Cancelled order, dispute resolution, or admin adjustment"></textarea>
                        </label>
                        <div class="admin-modal-actions">
                            <button type="button" class="admin-secondary-button" data-admin-modal-close>Cancel</button>
                            <button class="is-danger" type="submit">Refund order</button>
                        </div>
                    </form>
                </div>
            </dialog>
        @endcanany
    @endif

    <section class="admin-detail-grid">
        <article class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Cancellation state</h2>
                    <p>Buyer/seller cancellation requests and refund processing state.</p>
                </div>
            </div>
            @if ($order->latestCancellation)
                <dl class="admin-user-detail-list admin-detail-list">
                    <div><dt>Status</dt><dd>{{ str($order->latestCancellation->status)->replace('_', ' ')->title() }}</dd></div>
                    <div><dt>Refund</dt><dd>{{ str($order->latestCancellation->refund_status ?: 'none')->replace('_', ' ')->title() }}</dd></div>
                    <div><dt>Requested by</dt><dd>{{ $order->latestCancellation->requester?->name ?: 'System' }}</dd></div>
                    <div><dt>Responded by</dt><dd>{{ $order->latestCancellation->responder?->name ?: 'Pending' }}</dd></div>
                    <div><dt>Reason</dt><dd>{{ $order->latestCancellation->reason ?: 'No reason stored' }}</dd></div>
                    <div><dt>Response</dt><dd>{{ $order->latestCancellation->response_note ?: 'No response note' }}</dd></div>
                </dl>
            @else
                <p class="admin-empty-note">No cancellation request has been recorded for this order.</p>
            @endif
        </article>

        <article class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Invoice and receipt</h2>
                    <p>Generated after payment success and reused for email receipts.</p>
                </div>
            </div>
            @if ($order->invoice)
                <dl class="admin-user-detail-list admin-detail-list">
                    <div><dt>Invoice</dt><dd>{{ $order->invoice->code }}</dd></div>
                    <div><dt>Issued</dt><dd>{{ $order->invoice->issued_at?->format('M j, Y g:i A') ?? 'Not issued' }}</dd></div>
                    <div><dt>Amount</dt><dd>${{ number_format($order->invoice->amount_cents / 100, 2) }}</dd></div>
                    <div><dt>Platform fee</dt><dd>${{ number_format($order->invoice->platform_fee_cents / 100, 2) }}</dd></div>
                    <div><dt>Payment method</dt><dd>{{ $order->invoice->payment_method ?: 'Manual' }}</dd></div>
                    <div><dt>Transaction</dt><dd>{{ $order->invoice->transaction_id ?: 'No transaction ID' }}</dd></div>
                </dl>
            @else
                <p class="admin-empty-note">No invoice has been generated yet. Payment success will generate one automatically.</p>
            @endif
        </article>

        <article class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Manual payment</h2>
                    <p>Submitted buyer reference and admin review state.</p>
                </div>
            </div>
            @if ($payment)
                <dl class="admin-user-detail-list admin-detail-list">
                    <div><dt>Method</dt><dd>{{ $payment->method?->name ?? 'Unavailable' }}</dd></div>
                    <div><dt>Reference</dt><dd>{{ $payment->reference }}</dd></div>
                    <div><dt>Proof</dt><dd>{{ $payment->proof_reference ?: 'No proof reference' }}</dd></div>
                    <div><dt>Amount</dt><dd>${{ number_format($payment->amount_cents / 100, 0) }} {{ $payment->currency }}</dd></div>
                    <div><dt>Reviewed by</dt><dd>{{ $payment->adminReviewer?->name ?? $payment->reviewer?->name ?? 'Pending review' }}</dd></div>
                    <div><dt>Review note</dt><dd>{{ $payment->review_note ?: 'No review note' }}</dd></div>
                </dl>
                @if ($payment->status === 'pending')
                    @can('manual-payments.approve')
                        <button class="admin-inline-action-button" type="button" data-admin-modal-open="order-payment-review-modal">
                            Review payment
                        </button>
                    @endcan
                @endif
            @else
                <p class="admin-empty-note">This order has no manual payment submission.</p>
            @endif
        </article>

        <article class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Requirements</h2>
                    <p>Buyer-provided requirements stored with the order.</p>
                </div>
            </div>
            <div class="admin-card-list compact">
                @forelse ($requirements as $requirement)
                    <article class="admin-mini-card">
                        <div>
                            <strong>{{ $requirement['label'] ?? 'Requirement' }}</strong>
                            <p>{{ $requirement['answer'] ?? 'No answer stored.' }}</p>
                        </div>
                    </article>
                @empty
                    <p class="admin-empty-note">No buyer requirements are stored yet.</p>
                @endforelse
            </div>
        </article>
    </section>

    @if ($payment && $payment->status === 'pending')
        @can('manual-payments.approve')
            <dialog class="admin-modal" id="order-payment-review-modal" data-admin-modal>
                <div class="admin-modal-panel">
                    <div class="admin-modal-head">
                        <div>
                            <p class="admin-eyebrow">Manual payment</p>
                            <h2>Review {{ $payment->reference }}</h2>
                            <span>This decision updates the order payment lifecycle and notifies the buyer and seller.</span>
                        </div>
                        <button type="button" data-admin-modal-close aria-label="Close payment review modal">Close</button>
                    </div>
                    <form class="admin-detail-form admin-modal-form" method="POST" action="{{ route('admin.manual-payments.review', $payment) }}">
                        @csrf
                        @method('PATCH')
                        <label>
                            <span>Review note</span>
                            <textarea name="note" rows="4" placeholder="Optional payment review note"></textarea>
                        </label>
                        <div class="admin-modal-actions">
                            <button type="button" class="admin-secondary-button" data-admin-modal-close>Cancel</button>
                            <button type="submit" name="decision" value="approve">Approve payment</button>
                            <button class="is-danger" type="submit" name="decision" value="reject">Reject payment</button>
                        </div>
                    </form>
                </div>
            </dialog>
        @endcan
    @endif

    <section class="admin-detail-grid">
        <article class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Disputes</h2>
                    <p>Resolution cases attached to this order.</p>
                </div>
            </div>
            <div class="admin-card-list compact">
                @forelse ($order->disputes as $dispute)
                    <article class="admin-mini-card">
                        <div>
                            <strong>{{ $dispute->case_code }} - {{ $dispute->reason }}</strong>
                            <p>{{ str($dispute->status)->replace('_', ' ')->title() }} - {{ $dispute->assignedAdmin?->name ?? $dispute->assignedTo?->name ?? 'Unassigned' }}</p>
                        </div>
                        <a class="admin-panel-link" href="{{ route('admin.disputes.show', $dispute) }}">View case</a>
                    </article>
                @empty
                    <p class="admin-empty-note">No dispute cases are linked to this order.</p>
                @endforelse
            </div>
        </article>

        <article class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Open dispute</h2>
                    <p>Create a persisted resolution case from this order.</p>
                </div>
            </div>
            @can('disputes.resolve')
                <button class="admin-inline-action-button" type="button" data-admin-modal-open="order-dispute-modal">
                    Open dispute case
                </button>
            @else
                <p class="admin-empty-note">You can inspect existing cases but cannot open a dispute.</p>
            @endcan
        </article>
    </section>

    @can('disputes.resolve')
        <dialog class="admin-modal" id="order-dispute-modal" data-admin-modal>
            <div class="admin-modal-panel">
                <div class="admin-modal-head">
                    <div>
                        <p class="admin-eyebrow">Resolution center</p>
                        <h2>Open a dispute case</h2>
                        <span>Create a persisted case attached to order #{{ $order->code }}.</span>
                    </div>
                    <button type="button" data-admin-modal-close aria-label="Close dispute modal">Close</button>
                </div>
                <form class="admin-detail-form admin-modal-form" method="POST" action="{{ route('admin.orders.disputes.store', $order) }}">
                    @csrf
                    <label>
                        <span>Reason</span>
                        <input name="reason" type="text" maxlength="255" required placeholder="Delivery scope disagreement">
                    </label>
                    <label>
                        <span>Priority</span>
                        <select name="priority">
                            <option value="normal">Normal</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                        </select>
                    </label>
                    <label>
                        <span>Description</span>
                        <textarea name="description" rows="4" maxlength="3000" placeholder="Internal case context and first evidence note"></textarea>
                    </label>
                    <div class="admin-modal-actions">
                        <button type="button" class="admin-secondary-button" data-admin-modal-close>Cancel</button>
                        <button type="submit">Open dispute case</button>
                    </div>
                </form>
            </div>
        </dialog>
    @endcan

    <section class="admin-detail-grid">
        <article class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Order activity</h2>
                    <p>Database-backed audit history for this order.</p>
                </div>
            </div>
            <ol class="admin-activity-list">
                @forelse ($order->activities as $activity)
                    <li>
                        <strong>{{ $activity->title }}</strong>
                        {{ $activity->detail ? ' - '.$activity->detail : '' }}
                        <small>{{ $activity->adminActor?->name ?? $activity->actor?->name ?? 'System' }} - {{ $activity->created_at?->format('M j, Y g:i A') }}</small>
                    </li>
                @empty
                    <li>No activity entries have been recorded for this order yet.</li>
                @endforelse
            </ol>
        </article>

        <article class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Stored metadata</h2>
                    <p>Operational fields persisted with the order.</p>
                </div>
            </div>
            <div class="admin-user-detail-meta">
                <section>
                    <h3>Item summary</h3>
                    <p><span>{{ $metadata['itemSummary'] ?? 'No summary stored' }}</span></p>
                </section>
                <section>
                    <h3>Other metadata</h3>
                    <p>
                        @forelse (collect($metadata)->except(['itemSummary', 'quantity', 'requirements', 'activity']) as $key => $value)
                            <span>{{ str($key)->headline() }}: {{ is_scalar($value) ? $value : 'Stored' }}</span>
                        @empty
                            <em>No additional order metadata.</em>
                        @endforelse
                    </p>
                </section>
            </div>
        </article>
    </section>

    @include('admin.partials.modal-scripts')
@endsection
