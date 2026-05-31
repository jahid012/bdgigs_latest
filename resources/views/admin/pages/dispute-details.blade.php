@extends('admin.layouts.panel')

@section('title', 'Dispute Details')

@section('panel')
    @include('admin.partials.stats', ['stats' => $stats])

    @php
        $order = $dispute->order;
        $statusClass = in_array($dispute->status, ['resolved', 'closed'], true)
            ? 'status-completed'
            : (in_array($dispute->status, ['open', 'rejected'], true) ? 'status-cancelled' : 'status-delivered');
        $priorityClass = match ($dispute->priority) {
            'critical' => 'status-cancelled',
            'high' => 'status-delivered',
            default => 'status-progress',
        };
    @endphp

    <section class="admin-detail-layout">
        <article class="admin-panel admin-detail-summary">
            <div class="admin-detail-head admin-order-detail-head">
                <div>
                    <div class="admin-detail-badges">
                        <span class="admin-status-badge {{ $statusClass }}">{{ str($dispute->status)->replace('_', ' ')->title() }}</span>
                        <span class="admin-status-badge {{ $priorityClass }}">{{ str($dispute->priority)->title() }}</span>
                    </div>
                    <h2>{{ $dispute->case_code }} - {{ $dispute->reason }}</h2>
                    <p>{{ $dispute->description ?: 'No initial case description was recorded.' }}</p>
                </div>
            </div>

            <dl class="admin-user-detail-list admin-detail-list">
                <div><dt>Order</dt><dd>#{{ $order->code }}</dd></div>
                <div><dt>Opened by</dt><dd>{{ $dispute->openedByAdmin?->name ?? $dispute->openedBy?->name ?? 'System' }}</dd></div>
                <div><dt>Assigned to</dt><dd>{{ $dispute->assignedAdmin?->name ?? $dispute->assignedTo?->name ?? 'Unassigned' }}</dd></div>
                <div><dt>Opened</dt><dd>{{ $dispute->created_at?->format('M j, Y g:i A') ?? 'Unknown' }}</dd></div>
                <div><dt>Resolved by</dt><dd>{{ $dispute->resolvedByAdmin?->name ?? $dispute->resolvedBy?->name ?? 'Not resolved' }}</dd></div>
                <div><dt>Resolved at</dt><dd>{{ $dispute->resolved_at?->format('M j, Y g:i A') ?? 'Not resolved' }}</dd></div>
            </dl>

            <div class="admin-user-detail-actions">
                <a href="{{ route('admin.disputes') }}">Back to disputes</a>
                <a href="{{ route('admin.orders.show', $order->code) }}">View order</a>
                @if ($order->gig)
                    <a href="{{ route('admin.gigs.show', $order->gig) }}">View gig</a>
                @endif
            </div>
        </article>

        <aside class="admin-panel admin-detail-action-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Case action</h2>
                    <p>Open an action, review the impact, then confirm.</p>
                </div>
            </div>
            @can('disputes.resolve')
                <div class="admin-moderation-summary">
                    <span><strong>{{ str($dispute->status)->replace('_', ' ')->title() }}</strong>Current status</span>
                    <span><strong>{{ $dispute->assignedAdmin?->name ?? 'Unassigned' }}</strong>Case owner</span>
                </div>
                <div class="admin-moderation-action-list">
                    <button class="admin-moderation-action-button is-positive" type="button" data-admin-modal-open="dispute-update-modal">
                        <strong>Update case</strong>
                        <span>Change status, priority, assignment, resolution, and internal note.</span>
                    </button>
                    <button class="admin-moderation-action-button is-positive" type="button" data-admin-modal-open="dispute-join-modal">
                        <strong>Join case</strong>
                        <span>Assign yourself and move the dispute into admin review.</span>
                    </button>
                    <button class="admin-moderation-action-button is-warning" type="button" data-admin-modal-open="dispute-evidence-modal">
                        <strong>Request evidence</strong>
                        <span>Ask the buyer, seller, or both parties for more context.</span>
                    </button>
                    @can('payments.release')
                        <button class="admin-moderation-action-button is-danger" type="button" data-admin-modal-open="dispute-refund-modal">
                            <strong>Issue refund</strong>
                            <span>Resolve the dispute by refunding all or part of the linked order.</span>
                        </button>
                    @endcan
                </div>
            @else
                <p class="admin-empty-note">You can inspect this case but cannot change it.</p>
            @endcan
        </aside>
    </section>

    @can('disputes.resolve')
        <dialog class="admin-modal" id="dispute-update-modal" data-admin-modal>
            <div class="admin-modal-panel">
                <div class="admin-modal-head">
                    <div>
                        <p class="admin-eyebrow">Case update</p>
                        <h2>Update {{ $dispute->case_code }}</h2>
                        <span>Resolution is required when resolving, rejecting, or closing the case.</span>
                    </div>
                    <button type="button" data-admin-modal-close aria-label="Close dispute update modal">Close</button>
                </div>
                <form class="admin-detail-form admin-modal-form" method="POST" action="{{ route('admin.disputes.update', $dispute) }}">
                    @csrf
                    @method('PATCH')
                    <label>
                        <span>Status</span>
                        <select name="status">
                            @foreach ($statusOptions as $status)
                                <option value="{{ $status }}" @selected($dispute->status === $status)>{{ str($status)->replace('_', ' ')->title() }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span>Priority</span>
                        <select name="priority">
                            @foreach ($priorityOptions as $priority)
                                <option value="{{ $priority }}" @selected($dispute->priority === $priority)>{{ str($priority)->title() }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span>Assigned admin</span>
                        <select name="assigned_to_id">
                            <option value="">Unassigned</option>
                            @foreach ($assignees as $assignee)
                                <option value="{{ $assignee->id }}" @selected($dispute->assigned_to_admin_id === $assignee->id)>{{ $assignee->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span>Resolution</span>
                        <textarea name="resolution" rows="4" placeholder="Required when resolving or closing the case">{{ $dispute->resolution }}</textarea>
                    </label>
                    <label>
                        <span>Activity note</span>
                        <textarea name="note" rows="3" placeholder="Optional internal note for this update"></textarea>
                    </label>
                    <div class="admin-modal-actions">
                        <button type="button" class="admin-secondary-button" data-admin-modal-close>Cancel</button>
                        <button type="submit">Save case update</button>
                    </div>
                </form>
            </div>
        </dialog>

        <dialog class="admin-modal" id="dispute-join-modal" data-admin-modal>
            <div class="admin-modal-panel">
                <div class="admin-modal-head">
                    <div>
                        <p class="admin-eyebrow">Case ownership</p>
                        <h2>Join {{ $dispute->case_code }}</h2>
                        <span>This assigns the case if it is currently unassigned and records an activity entry.</span>
                    </div>
                    <button type="button" data-admin-modal-close aria-label="Close join modal">Close</button>
                </div>
                <form class="admin-detail-form admin-modal-form" method="POST" action="{{ route('admin.disputes.join', $dispute) }}">
                    @csrf
                    <label>
                        <span>Join note</span>
                        <textarea name="note" rows="4" placeholder="Optional note for participants"></textarea>
                    </label>
                    <div class="admin-modal-actions">
                        <button type="button" class="admin-secondary-button" data-admin-modal-close>Cancel</button>
                        <button type="submit">Join case</button>
                    </div>
                </form>
            </div>
        </dialog>

        <dialog class="admin-modal" id="dispute-evidence-modal" data-admin-modal>
            <div class="admin-modal-panel">
                <div class="admin-modal-head">
                    <div>
                        <p class="admin-eyebrow">Evidence request</p>
                        <h2>Request evidence</h2>
                        <span>The case will move into evidence requested status.</span>
                    </div>
                    <button type="button" data-admin-modal-close aria-label="Close evidence modal">Close</button>
                </div>
                <form class="admin-detail-form admin-modal-form" method="POST" action="{{ route('admin.disputes.evidence-request', $dispute) }}">
                    @csrf
                    <label>
                        <span>Recipient</span>
                        <select name="recipient_id">
                            <option value="">Buyer and seller</option>
                            @if ($order->buyer)
                                <option value="{{ $order->buyer->id }}">Buyer - {{ $order->buyer->name }}</option>
                            @endif
                            @if ($order->seller)
                                <option value="{{ $order->seller->id }}">Seller - {{ $order->seller->name }}</option>
                            @endif
                        </select>
                    </label>
                    <label>
                        <span>Evidence request</span>
                        <textarea name="note" rows="4" required placeholder="Explain what evidence is needed"></textarea>
                    </label>
                    <div class="admin-modal-actions">
                        <button type="button" class="admin-secondary-button" data-admin-modal-close>Cancel</button>
                        <button type="submit">Request evidence</button>
                    </div>
                </form>
            </div>
        </dialog>

        @can('payments.release')
            <dialog class="admin-modal" id="dispute-refund-modal" data-admin-modal>
                <div class="admin-modal-panel">
                    <div class="admin-modal-head">
                        <div>
                            <p class="admin-eyebrow">Dispute refund</p>
                            <h2>Issue refund</h2>
                            <span>Refundable balance: ${{ number_format(max(0, ($order->price_cents - (int) $order->refund_amount_cents)) / 100, 2) }}.</span>
                        </div>
                        <button type="button" data-admin-modal-close aria-label="Close dispute refund modal">Close</button>
                    </div>
                    <form class="admin-detail-form admin-modal-form" method="POST" action="{{ route('admin.disputes.refund', $dispute) }}">
                        @csrf
                        <label>
                            <span>Refund amount</span>
                            <input type="number" name="amount" min="0.01" step="0.01" max="{{ number_format($order->price_cents / 100, 2, '.', '') }}" value="{{ number_format(max(0, ($order->price_cents - (int) $order->refund_amount_cents)) / 100, 2, '.', '') }}">
                        </label>
                        <label>
                            <span>Refund reason</span>
                            <textarea name="reason" rows="4" placeholder="Resolution decision and refund reason"></textarea>
                        </label>
                        <div class="admin-modal-actions">
                            <button type="button" class="admin-secondary-button" data-admin-modal-close>Cancel</button>
                            <button class="is-danger" type="submit">Issue refund</button>
                        </div>
                    </form>
                </div>
            </dialog>
        @endcan
    @endcan

    <section class="admin-detail-grid">
        <article class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Linked order</h2>
                    <p>Buyer, seller, service, and delivery context.</p>
                </div>
            </div>
            <dl class="admin-user-detail-list admin-detail-list">
                <div><dt>Buyer</dt><dd>{{ $order->buyer?->name ?: $order->buyer_name ?: 'Unavailable' }}</dd></div>
                <div><dt>Seller</dt><dd>{{ $order->seller?->name ?: $order->seller_name ?: 'Unavailable' }}</dd></div>
                <div><dt>Service</dt><dd>{{ $order->service }}</dd></div>
                <div><dt>Order status</dt><dd>{{ $order->status }}</dd></div>
                <div><dt>Due</dt><dd>{{ $order->due_date?->format('M j, Y') ?? 'No due date' }}</dd></div>
                <div><dt>Amount</dt><dd>${{ number_format($order->price_cents / 100, 0) }}</dd></div>
            </dl>
        </article>

        <article class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Conversation evidence</h2>
                    <p>Linked thread messages when a conversation is attached.</p>
                </div>
            </div>
            <ol class="admin-activity-list">
                @forelse ($dispute->conversation?->messages ?? [] as $message)
                    <li>
                        <strong>{{ $message->sender?->name ?? $message->sender_name }}</strong>
                        {{ $message->body }}
                        <small>{{ $message->sent_at?->format('M j, Y g:i A') ?? $message->created_at?->format('M j, Y g:i A') }}</small>
                    </li>
                @empty
                    <li>No conversation evidence is linked to this dispute.</li>
                @endforelse
            </ol>
        </article>
    </section>

    <section class="admin-detail-grid">
        <article class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Resolution activity</h2>
                    <p>Case updates and internal notes.</p>
                </div>
            </div>
            <ol class="admin-activity-list">
                @forelse ($dispute->activities as $activity)
                    <li>
                        <strong>{{ $activity->title }}</strong>
                        {{ $activity->detail ? ' - '.$activity->detail : '' }}
                        <small>{{ $activity->adminActor?->name ?? $activity->actor?->name ?? 'System' }} - {{ $activity->created_at?->format('M j, Y g:i A') }}</small>
                    </li>
                @empty
                    <li>No dispute activity has been recorded yet.</li>
                @endforelse
            </ol>
        </article>

        <article class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Resolution summary</h2>
                    <p>Current final or working outcome.</p>
                </div>
            </div>
            <div class="admin-user-detail-meta">
                <section>
                    <h3>Decision</h3>
                    <p><span>{{ $dispute->resolution ?: 'No resolution text yet.' }}</span></p>
                </section>
                <section>
                    <h3>Metadata</h3>
                    <p>
                        @forelse (($dispute->metadata ?? []) as $key => $value)
                            <span>{{ str($key)->headline() }}: {{ is_scalar($value) ? $value : 'Stored' }}</span>
                        @empty
                            <em>No metadata stored.</em>
                        @endforelse
                    </p>
                </section>
            </div>
        </article>
    </section>
    @include('admin.partials.modal-scripts')
@endsection
