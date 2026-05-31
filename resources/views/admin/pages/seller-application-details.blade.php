@extends('admin.layouts.panel')

@section('title', 'Seller Application Details')

@section('panel')
    @include('admin.partials.stats', ['stats' => $stats])

    <section class="admin-detail-layout">
        <article class="admin-panel admin-detail-summary">
            <div class="admin-user-detail-head">
                <span>{{ strtoupper(substr($seller->name ?: $seller->email, 0, 1)) }}</span>
                <div>
                    <h2>{{ $seller->name ?: $seller->email }}</h2>
                    <p>{{ '@'.$seller->username }} - {{ $seller->email }}</p>
                </div>
                <b class="admin-status-badge {{ $seller->seller_status === 'approved' ? 'is-good' : ($seller->seller_status === 'rejected' ? 'is-danger' : 'is-warn') }}">{{ str($seller->seller_status)->replace('_', ' ')->title() }}</b>
            </div>

            <dl class="admin-user-detail-list admin-detail-list">
                <div><dt>Country</dt><dd>{{ $seller->country ?: 'Unknown' }}</dd></div>
                <div><dt>Profile type</dt><dd>{{ $seller->profile_type ?: 'buyer' }}</dd></div>
                <div><dt>Review reason</dt><dd>{{ $seller->seller_status_reason ?: 'None' }}</dd></div>
                <div><dt>Reviewed at</dt><dd>{{ $seller->seller_status_reviewed_at?->format('M j, Y g:i A') ?? 'Not reviewed' }}</dd></div>
            </dl>

            <div class="admin-user-detail-actions">
                <a href="{{ route('admin.seller-applications') }}">Back to applications</a>
                <a href="{{ route('admin.users.show', $seller) }}">View user</a>
            </div>
        </article>

        <aside class="admin-panel admin-detail-action-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Review action</h2>
                    <p>Open an action, review the impact, then confirm.</p>
                </div>
            </div>
            @can('users.verify')
                <div class="admin-moderation-summary">
                    <span><strong>{{ str($seller->seller_status ?: 'not_applied')->replace('_', ' ')->title() }}</strong>Seller state</span>
                    <span><strong>{{ $seller->seller_status_reviewed_at?->diffForHumans() ?? 'Not reviewed' }}</strong>Last review</span>
                </div>
                <div class="admin-moderation-action-list">
                    <button class="admin-moderation-action-button is-positive" type="button" data-admin-modal-open="seller-approve-modal">
                        <strong>Approve seller</strong>
                        <span>Allow this seller to submit and publish marketplace gigs.</span>
                    </button>
                    <button class="admin-moderation-action-button is-danger" type="button" data-admin-modal-open="seller-reject-modal">
                        <strong>Reject seller</strong>
                        <span>Return the application with a reason the seller can act on.</span>
                    </button>
                </div>
            @else
                <p class="admin-empty-note">You can inspect this seller but cannot review applications.</p>
            @endcan
        </aside>
    </section>

    @can('users.verify')
        <dialog class="admin-modal" id="seller-approve-modal" data-admin-modal>
            <div class="admin-modal-panel">
                <div class="admin-modal-head">
                    <div>
                        <p class="admin-eyebrow">Seller application</p>
                        <h2>Approve {{ $seller->name ?: $seller->email }}</h2>
                        <span>Approval lets the seller submit and publish gigs.</span>
                    </div>
                    <button type="button" data-admin-modal-close aria-label="Close seller approve modal">Close</button>
                </div>
                <form class="admin-detail-form admin-modal-form" method="POST" action="{{ route('admin.seller-applications.approve', $seller) }}">
                    @csrf
                    <label>
                        <span>Approval note</span>
                        <textarea name="reason" rows="4" placeholder="Optional approval note"></textarea>
                    </label>
                    <div class="admin-modal-actions">
                        <button type="button" class="admin-secondary-button" data-admin-modal-close>Cancel</button>
                        <button type="submit">Approve seller</button>
                    </div>
                </form>
            </div>
        </dialog>

        <dialog class="admin-modal" id="seller-reject-modal" data-admin-modal>
            <div class="admin-modal-panel">
                <div class="admin-modal-head">
                    <div>
                        <p class="admin-eyebrow">Seller application</p>
                        <h2>Reject {{ $seller->name ?: $seller->email }}</h2>
                        <span>The rejection reason is stored in the seller application history.</span>
                    </div>
                    <button type="button" data-admin-modal-close aria-label="Close seller reject modal">Close</button>
                </div>
                <form class="admin-detail-form admin-modal-form" method="POST" action="{{ route('admin.seller-applications.reject', $seller) }}">
                    @csrf
                    <label>
                        <span>Rejection reason</span>
                        <textarea name="reason" rows="4" required placeholder="Tell the seller what to fix"></textarea>
                    </label>
                    <div class="admin-modal-actions">
                        <button type="button" class="admin-secondary-button" data-admin-modal-close>Cancel</button>
                        <button class="is-danger" type="submit">Reject seller</button>
                    </div>
                </form>
            </div>
        </dialog>
    @endcan

    <section class="admin-detail-grid">
        <article class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Status history</h2>
                    <p>Seller application lifecycle events.</p>
                </div>
            </div>
            <div class="admin-card-list compact">
                @forelse ($seller->sellerStatusEvents as $event)
                    <article class="admin-mini-card">
                        <div>
                            <strong>{{ str($event->from_status ?: 'start')->replace('_', ' ')->title() }} to {{ str($event->to_status)->replace('_', ' ')->title() }}</strong>
                            <p>{{ $event->reason ?: 'No reason recorded.' }} - {{ $event->created_at?->format('M j, Y g:i A') }}</p>
                        </div>
                        <span>{{ $event->adminActor?->name ?? $event->actor?->name ?? 'System' }}</span>
                    </article>
                @empty
                    <p class="admin-empty-note">No seller status history is recorded.</p>
                @endforelse
            </div>
        </article>

        <article class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Recent gigs</h2>
                    <p>Catalog records connected to this seller.</p>
                </div>
            </div>
            <div class="admin-card-list compact">
                @forelse ($seller->gigs as $gig)
                    <article class="admin-mini-card">
                        <div>
                            <strong>{{ $gig->title }}</strong>
                            <p>{{ $gig->category_label ?: 'Uncategorized' }}</p>
                        </div>
                        <span class="admin-status-badge {{ $gig->status_class }}">{{ str($gig->status)->replace('_', ' ')->title() }}</span>
                    </article>
                @empty
                    <p class="admin-empty-note">This seller has not created gigs yet.</p>
                @endforelse
            </div>
        </article>
    </section>
    @include('admin.partials.modal-scripts')
@endsection
