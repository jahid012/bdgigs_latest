@extends('admin.layouts.panel')

@section('title', 'User Details')

@section('panel')
    @include('admin.partials.stats', ['stats' => $stats])

    <section class="admin-user-detail-grid">
        <article class="admin-panel admin-user-detail-card">
            <div class="admin-user-detail-head">
                <span>{{ strtoupper(substr($targetUser->name ?: $targetUser->email, 0, 1)) }}</span>
                <div>
                    <h2>{{ $targetUser->name ?: $targetUser->email }}</h2>
                    <p>{{ '@'.$targetUser->username }} - {{ $targetUser->email }}</p>
                </div>
                <b class="admin-status-badge {{ $account['status_class'] }}">{{ $account['status'] }}</b>
            </div>

            <dl class="admin-user-detail-list">
                <div><dt>Profile</dt><dd>{{ $account['profile_type'] }}</dd></div>
                <div><dt>Country</dt><dd>{{ $account['country'] }}</dd></div>
                <div><dt>Email verified</dt><dd>{{ $targetUser->email_verified_at?->format('M j, Y') ?? 'No' }}</dd></div>
                <div><dt>Verification</dt><dd>{{ $account['verification'] }}</dd></div>
                <div><dt>Suspension reason</dt><dd>{{ $targetUser->suspension_reason ?: 'None' }}</dd></div>
                <div><dt>Deactivation reason</dt><dd>{{ $targetUser->deactivation_reason ?: 'None' }}</dd></div>
                <div><dt>Joined</dt><dd>{{ $targetUser->created_at?->format('M j, Y') ?? 'Unknown' }}</dd></div>
                <div><dt>Last seen</dt><dd>{{ $targetUser->last_seen_at?->diffForHumans() ?? 'Not recorded' }}</dd></div>
            </dl>

            <div class="admin-user-detail-actions">
                <a href="{{ route('admin.users') }}">Back to users</a>
                @if ($impersonationAllowed)
                    <form method="POST" action="{{ route('admin.users.impersonate', $targetUser) }}">
                        @csrf
                        <button type="submit">Login as this user</button>
                    </form>
                @endif
                @can('users.verify')
                    <form method="POST" action="{{ route('admin.users.verify', $targetUser) }}">
                        @csrf
                        <button type="submit">Verify user</button>
                    </form>
                @endcan
                @can('users.suspend')
                    @if ($account['can_restore'])
                        <form method="POST" action="{{ route('admin.users.restore', $targetUser) }}">
                            @csrf
                            <label>
                                <span class="sr-only">Reactivation reason</span>
                                <textarea name="reason" rows="2" placeholder="Reason for reactivation"></textarea>
                            </label>
                            <button type="submit">Restore user</button>
                        </form>
                    @elseif ($account['can_suspend'])
                        <form method="POST" action="{{ route('admin.users.suspend', $targetUser) }}">
                            @csrf
                            <label>
                                <span class="sr-only">Suspension reason</span>
                                <textarea name="reason" rows="2" placeholder="Reason for suspension"></textarea>
                            </label>
                            <button class="is-danger" type="submit">Suspend user</button>
                        </form>
                    @endif
                    @if ($account['can_deactivate'])
                        <form method="POST" action="{{ route('admin.users.deactivate', $targetUser) }}">
                            @csrf
                            <label>
                                <span class="sr-only">Deactivation reason</span>
                                <textarea name="reason" rows="2" placeholder="Reason for deactivation"></textarea>
                            </label>
                            <button class="is-danger" type="submit">Deactivate user</button>
                        </form>
                    @endif
                @endcan
            </div>
        </article>

        <aside class="admin-panel admin-user-detail-aside">
            <div class="admin-panel-head">
                <div>
                    <h2>Access and profiles</h2>
                    <p>Roles and persisted account records.</p>
                </div>
            </div>
            <div class="admin-user-detail-meta">
                <section>
                    <h3>Roles</h3>
                    <p>
                        @forelse ($targetUser->roles as $role)
                            <span>{{ str($role->name)->replace('_', ' ')->title() }}</span>
                        @empty
                            <em>No admin roles</em>
                        @endforelse
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
                        <span>{{ $event->actor?->name ?? 'System' }}</span>
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
                        @can('users.verify')
                            <form method="POST" action="{{ route('admin.users.identity.review', [$targetUser, $submission]) }}">
                                @csrf
                                @method('PATCH')
                                <label>
                                    <span class="sr-only">Identity action</span>
                                    <select name="action">
                                        <option value="under_review">Mark under review</option>
                                        <option value="approve">Approve</option>
                                        <option value="reject">Reject</option>
                                        <option value="request_documents">Request documents</option>
                                    </select>
                                </label>
                                <label>
                                    <span class="sr-only">Identity note</span>
                                    <textarea name="note" rows="2" placeholder="Review note or document request"></textarea>
                                </label>
                                <button type="submit">Save</button>
                            </form>
                        @endcan
                    </article>
                @empty
                    <p class="admin-empty-note">No identity submissions are recorded.</p>
                @endforelse
            </div>
        </article>
    </section>

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
@endsection
