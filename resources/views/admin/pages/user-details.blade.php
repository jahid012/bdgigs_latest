@extends('admin.layouts.panel')

@section('title', 'User Details')

@section('panel')
    @include('admin.partials.stats', ['stats' => $stats])

    @php
        $adminUser = auth('admin')->user();
        $identityReviewAllowed = $adminUser?->can('users.verify');
        $accountActions = collect([
            [
                'key' => 'verify',
                'label' => 'Verify user',
                'title' => 'Verify this user',
                'description' => 'Mark the account as verified and confirm the email if it is still unverified.',
                'route' => route('admin.users.verify', $targetUser),
                'permission' => 'users.verify',
                'available' => ! $account['email_verified'] || $targetUser->verification_status !== 'verified',
                'shows_reason' => false,
                'requires_reason' => false,
                'tone' => 'positive',
            ],
            [
                'key' => 'restore',
                'label' => 'Restore user',
                'title' => 'Restore this user',
                'description' => 'Reactivate marketplace access and clear suspension or deactivation state.',
                'route' => route('admin.users.restore', $targetUser),
                'permission' => 'users.suspend',
                'available' => $account['can_restore'],
                'shows_reason' => true,
                'requires_reason' => false,
                'tone' => 'positive',
            ],
            [
                'key' => 'suspend',
                'label' => 'Suspend user',
                'title' => 'Suspend this user',
                'description' => 'Pause account access, end active sessions, and record an audit reason.',
                'route' => route('admin.users.suspend', $targetUser),
                'permission' => 'users.suspend',
                'available' => $account['can_suspend'],
                'shows_reason' => true,
                'requires_reason' => true,
                'tone' => 'warning',
            ],
            [
                'key' => 'deactivate',
                'label' => 'Deactivate user',
                'title' => 'Deactivate this user',
                'description' => 'Deactivate this marketplace account and end active sessions.',
                'route' => route('admin.users.deactivate', $targetUser),
                'permission' => 'users.suspend',
                'available' => $account['can_deactivate'],
                'shows_reason' => true,
                'requires_reason' => true,
                'tone' => 'danger',
            ],
        ])->filter(fn ($action) => $action['available'] && $adminUser?->can($action['permission']))->values();
    @endphp

    <section class="admin-user-detail-grid">
        <article class="admin-panel admin-user-detail-card">
            <div class="admin-user-detail-head">
                <span>{{ strtoupper(substr($targetUser->name ?: $targetUser->email, 0, 1)) }}</span>
                <div>
                    <h2>{{ $targetUser->name ?: $targetUser->email }}</h2>
                    <p>
                        @if ($targetUser->username)
                            {{ '@'.$targetUser->username }} -
                        @endif
                        {{ $targetUser->email }}
                    </p>
                </div>
                <b class="admin-status-badge {{ $account['status_class'] }}">{{ $account['status'] }}</b>
            </div>

            <dl class="admin-user-detail-list">
                <div><dt>Profile</dt><dd>{{ $account['profile_type'] }}</dd></div>
                <div><dt>Country</dt><dd>{{ $account['country'] }}</dd></div>
                <div><dt>Email verified</dt><dd>{{ $targetUser->email_verified_at?->format('M j, Y') ?? 'No' }}</dd></div>
                <div><dt>Verification</dt><dd>{{ $account['verification'] }}</dd></div>
                <div><dt>Seller status</dt><dd>{{ $account['seller_status'] }}</dd></div>
                <div><dt>Last seen</dt><dd>{{ $targetUser->last_seen_at?->diffForHumans() ?? 'Not recorded' }}</dd></div>
                <div><dt>Suspension reason</dt><dd>{{ $targetUser->suspension_reason ?: 'None' }}</dd></div>
                <div><dt>Deactivation reason</dt><dd>{{ $targetUser->deactivation_reason ?: 'None' }}</dd></div>
                <div><dt>Joined</dt><dd>{{ $targetUser->created_at?->format('M j, Y') ?? 'Unknown' }}</dd></div>
            </dl>

            <div class="admin-user-detail-actions">
                <a href="{{ route('admin.users') }}">Back to users</a>
                @if ($impersonationAllowed)
                    <form method="POST" action="{{ route('admin.users.impersonate', $targetUser) }}">
                        @csrf
                        <button type="submit">Login as this user</button>
                    </form>
                @endif
            </div>
        </article>

        <aside class="admin-panel admin-user-detail-aside">
            <div class="admin-panel-head">
                <div>
                    <h2>Account actions</h2>
                    <p>Open an action, review the impact, then confirm.</p>
                </div>
            </div>

            <div class="admin-moderation-summary">
                <span><strong>{{ $account['email_verified'] ? 'Verified' : 'Unverified' }}</strong>Email</span>
                <span><strong>{{ $targetUser->last_seen_at?->diffForHumans() ?? 'Never' }}</strong>Last seen</span>
            </div>

            @if ($accountActions->isNotEmpty())
                <div class="admin-moderation-action-list admin-account-action-list">
                    @foreach ($accountActions as $accountAction)
                        @php($modalId = 'user-action-'.$accountAction['key'])
                        <button
                            class="admin-moderation-action-button is-{{ $accountAction['tone'] }}"
                            type="button"
                            data-admin-modal-open="{{ $modalId }}"
                        >
                            <strong>{{ $accountAction['label'] }}</strong>
                            <span>{{ $accountAction['description'] }}</span>
                        </button>
                    @endforeach
                </div>
            @else
                <p class="admin-empty-note">No account actions are available for your role and this account state.</p>
            @endif

            <div class="admin-user-detail-meta">
                <section>
                    <h3>Marketplace access</h3>
                    <p>
                        <span>{{ $targetUser->profile_type ? str($targetUser->profile_type)->title() : 'Buyer' }}</span>
                        <span>{{ $targetUser->seller_status ? str($targetUser->seller_status)->replace('_', ' ')->title() : 'Not applied' }}</span>
                    </p>
                </section>
                <section>
                    <h3>Profile records</h3>
                    <p>
                        <span>{{ $targetUser->buyerProfile ? 'Buyer profile' : 'No buyer profile' }}</span>
                        <span>{{ $targetUser->sellerProfile ? 'Seller profile' : 'No seller profile' }}</span>
                        <span>{{ $targetUser->billingProfile ? 'Billing profile' : 'No billing profile' }}</span>
                    </p>
                </section>
            </div>
        </aside>
    </section>

    @foreach ($accountActions as $accountAction)
        @php($modalId = 'user-action-'.$accountAction['key'])
        <dialog class="admin-modal" id="{{ $modalId }}" data-admin-modal data-admin-user-action-modal>
            <div class="admin-modal-panel">
                <div class="admin-modal-head">
                    <div>
                        <p class="admin-eyebrow">User management</p>
                        <h2>{{ $accountAction['title'] }}</h2>
                        <span>{{ $accountAction['description'] }}</span>
                    </div>
                    <button type="button" data-admin-modal-close aria-label="Close {{ $accountAction['label'] }} modal">Close</button>
                </div>
                <form class="admin-detail-form admin-modal-form" method="POST" action="{{ $accountAction['route'] }}">
                    @csrf
                    @if ($accountAction['shows_reason'])
                        <label>
                            <span>Reason{{ $accountAction['requires_reason'] ? ' (required)' : ' (optional)' }}</span>
                            <textarea
                                name="reason"
                                rows="4"
                                placeholder="Add a clear internal audit note"
                                @if ($accountAction['requires_reason']) required @endif
                            ></textarea>
                        </label>
                    @else
                        <p class="admin-modal-note">This action does not require a reason. It will still be tracked in the account lifecycle.</p>
                    @endif
                    <div class="admin-modal-actions">
                        <button type="button" class="admin-secondary-button" data-admin-modal-close>Cancel</button>
                        <button class="{{ $accountAction['tone'] === 'danger' ? 'is-danger' : '' }}" type="submit">
                            Confirm {{ strtolower($accountAction['label']) }}
                        </button>
                    </div>
                </form>
            </div>
        </dialog>
    @endforeach

    <section class="admin-workflow-grid admin-user-activity-grid">
        <article class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Status history</h2>
                    <p>Suspension, reactivation, deactivation, and verification lifecycle records.</p>
                </div>
            </div>
            <div class="admin-card-list compact">
                @forelse ($targetUser->accountStatusEvents as $event)
                    <article class="admin-mini-card">
                        <div>
                            <strong>{{ str($event->event_type)->replace('_', ' ')->title() }}</strong>
                            <p>{{ $event->reason ?: 'No reason recorded.' }} - {{ $event->created_at?->format('M j, Y g:i A') }}</p>
                        </div>
                        <span>{{ $event->adminActor?->name ?? $event->actor?->name ?? 'System' }}</span>
                    </article>
                @empty
                    <p class="admin-empty-note">No account status changes are recorded yet.</p>
                @endforelse
            </div>
        </article>

        <article class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Recent gigs</h2>
                    <p>Latest seller catalog records.</p>
                </div>
            </div>
            <div class="admin-card-list compact">
                @forelse ($recentGigs as $gig)
                    <article class="admin-mini-card">
                        <div>
                            <strong>{{ $gig->title }}</strong>
                            <p>{{ $gig->category_label ?: 'Uncategorized' }} - {{ $gig->updated_at?->diffForHumans() ?? 'Unknown' }}</p>
                        </div>
                        <span class="admin-status-badge {{ $gig->status_class }}">{{ $gig->status }}</span>
                    </article>
                @empty
                    <p class="admin-empty-note">This user has no seller gigs yet.</p>
                @endforelse
            </div>
        </article>

        <article class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Verification submissions</h2>
                    <p>Latest internal identity review records.</p>
                </div>
            </div>
            <div class="admin-card-list compact">
                @forelse ($targetUser->identityVerificationSubmissions as $submission)
                    @php($details = $submission->details ?? [])
                    @php($identityModalId = 'identity-review-'.$submission->id)
                    <article class="admin-mini-card">
                        <div>
                            <strong>{{ $details['legalName'] ?? 'Identity submission' }}</strong>
                            <p>{{ $details['documentType'] ?? 'Document' }} - {{ $submission->submitted_at?->diffForHumans() ?? $submission->created_at?->diffForHumans() }}</p>
                            @if ($submission->document_path)
                                <p><a href="{{ $submission->document_path }}" target="_blank" rel="noreferrer">Open document</a></p>
                            @endif
                            @if ($submission->review_note || $submission->additional_document_note)
                                <p>{{ $submission->review_note ?: $submission->additional_document_note }}</p>
                            @endif
                        </div>
                        <span class="admin-status-badge {{ in_array($submission->status, ['submitted', 'under_review', 'additional_document_required', 'review'], true) ? 'is-warn' : ($submission->status === 'rejected' ? 'is-danger' : 'is-good') }}">{{ str($submission->status)->replace('_', ' ')->title() }}</span>
                        @if ($identityReviewAllowed)
                            <button class="admin-inline-action-button" type="button" data-admin-modal-open="{{ $identityModalId }}">Review identity</button>
                        @endif
                    </article>
                @empty
                    <p class="admin-empty-note">No identity submissions are recorded.</p>
                @endforelse
            </div>
        </article>
    </section>

    @if ($identityReviewAllowed)
        @foreach ($targetUser->identityVerificationSubmissions as $submission)
            @php($details = $submission->details ?? [])
            @php($identityModalId = 'identity-review-'.$submission->id)
            <dialog class="admin-modal" id="{{ $identityModalId }}" data-admin-modal data-admin-identity-modal>
                <div class="admin-modal-panel">
                    <div class="admin-modal-head">
                        <div>
                            <p class="admin-eyebrow">Identity review</p>
                            <h2>{{ $details['legalName'] ?? 'Identity submission' }}</h2>
                            <span>Choose the review state and add a note when more context is needed.</span>
                        </div>
                        <button type="button" data-admin-modal-close aria-label="Close identity review modal">Close</button>
                    </div>
                    <form class="admin-detail-form admin-modal-form" method="POST" action="{{ route('admin.users.identity.review', [$targetUser, $submission]) }}">
                        @csrf
                        @method('PATCH')
                        <label>
                            <span>Identity action</span>
                            <select name="action">
                                <option value="under_review">Mark under review</option>
                                <option value="approve">Approve</option>
                                <option value="reject">Reject</option>
                                <option value="request_documents">Request documents</option>
                            </select>
                        </label>
                        <label>
                            <span>Review note</span>
                            <textarea name="note" rows="4" placeholder="Review note or document request"></textarea>
                        </label>
                        <div class="admin-modal-actions">
                            <button type="button" class="admin-secondary-button" data-admin-modal-close>Cancel</button>
                            <button type="submit">Save review</button>
                        </div>
                    </form>
                </div>
            </dialog>
        @endforeach
    @endif

    <section class="admin-page-grid admin-user-order-grid">
        @foreach (['Buyer orders' => $recentBuyerOrders, 'Seller orders' => $recentSellerOrders] as $heading => $orders)
            <article class="admin-panel admin-table-panel">
                <div class="admin-panel-head">
                    <div>
                        <h2>{{ $heading }}</h2>
                        <p>Most recent marketplace order activity.</p>
                    </div>
                </div>
                <div class="admin-table-wrap">
                    <table>
                        <thead>
                            <tr><th>Order</th><th>Service</th><th>Buyer</th><th>Seller</th><th>Status</th><th>Amount</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($orders as $order)
                                <tr>
                                    <td>{{ $order['id'] }}</td>
                                    <td>{{ $order['service'] }}</td>
                                    <td>{{ $order['buyer'] }}</td>
                                    <td>{{ $order['seller'] }}</td>
                                    <td><span class="admin-status-badge {{ $order['status_class'] }}">{{ $order['status'] }}</span></td>
                                    <td>{{ $order['amount'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6">No linked orders yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>
        @endforeach
    </section>

    @if ($accountActions->isNotEmpty() || $identityReviewAllowed)
        @include('admin.partials.modal-scripts')
    @endif
@endsection
