@extends('admin.layouts.panel')

@section('title', 'Moderation Report Details')

@section('panel')
    @include('admin.partials.stats', ['stats' => $stats])

    <section class="admin-detail-layout">
        <article class="admin-panel admin-detail-summary">
            <div class="admin-detail-head admin-order-detail-head">
                <div>
                    <div class="admin-detail-badges">
                        <span class="admin-status-badge {{ $report->status === 'resolved' ? 'is-good' : ($report->status === 'rejected' ? 'is-danger' : 'is-warn') }}">{{ str($report->status)->title() }}</span>
                        <span class="admin-status-badge status-progress">{{ str($report->type)->title() }}</span>
                    </div>
                    <h2>{{ $report->code }} - {{ $report->reason }}</h2>
                    <p>{{ $report->description ?: 'No additional description was provided.' }}</p>
                </div>
            </div>

            <dl class="admin-user-detail-list admin-detail-list">
                <div><dt>Reporter</dt><dd>{{ $report->reporter?->name ?? 'Unknown' }}</dd></div>
                <div><dt>Reported user</dt><dd>{{ $report->reportedUser?->name ?? 'None' }}</dd></div>
                <div><dt>Assigned to</dt><dd>{{ $report->assignedAdmin?->name ?? $report->assignedTo?->name ?? 'Unassigned' }}</dd></div>
                <div><dt>Resolved by</dt><dd>{{ $report->resolvedByAdmin?->name ?? $report->resolvedBy?->name ?? 'Not resolved' }}</dd></div>
                <div><dt>Resolution</dt><dd>{{ $report->resolution_note ?: 'No resolution note yet.' }}</dd></div>
                <div><dt>Created</dt><dd>{{ $report->created_at?->format('M j, Y g:i A') }}</dd></div>
            </dl>

            <div class="admin-user-detail-actions">
                <a href="{{ route('admin.moderation-reports') }}">Back to reports</a>
                @if ($report->reportedUser)
                    <a href="{{ route('admin.users.show', $report->reportedUser) }}">View reported user</a>
                @endif
            </div>
        </article>

        <aside class="admin-panel admin-detail-action-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Report status</h2>
                    <p>Open the decision modal before changing the report state.</p>
                </div>
            </div>
            @can('reports.manage')
                <div class="admin-moderation-summary">
                    <span><strong>{{ str($report->status)->title() }}</strong>Current status</span>
                    <span><strong>{{ $report->assignedAdmin?->name ?? 'Unassigned' }}</strong>Assigned admin</span>
                </div>
                <button class="admin-moderation-action-button is-positive" type="button" data-admin-modal-open="report-status-modal">
                    <strong>Update report</strong>
                    <span>Change the report status and save the resolution note.</span>
                </button>
            @else
                <p class="admin-empty-note">You can inspect reports but cannot update them.</p>
            @endcan
        </aside>
    </section>

    @can('reports.manage')
        <dialog class="admin-modal" id="report-status-modal" data-admin-modal>
            <div class="admin-modal-panel">
                <div class="admin-modal-head">
                    <div>
                        <p class="admin-eyebrow">Moderation report</p>
                        <h2>Update {{ $report->code }}</h2>
                        <span>Status updates notify the reporter when appropriate.</span>
                    </div>
                    <button type="button" data-admin-modal-close aria-label="Close report status modal">Close</button>
                </div>
                <form class="admin-detail-form admin-modal-form" method="POST" action="{{ route('admin.moderation-reports.update', $report) }}">
                    @csrf
                    @method('PATCH')
                    <label>
                        <span>Status</span>
                        <select name="status">
                            @foreach ($statusOptions as $status)
                                <option value="{{ $status }}" @selected($report->status === $status)>{{ str($status)->title() }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span>Resolution note</span>
                        <textarea name="note" rows="4" placeholder="What action was taken?">{{ $report->resolution_note }}</textarea>
                    </label>
                    <div class="admin-modal-actions">
                        <button type="button" class="admin-secondary-button" data-admin-modal-close>Cancel</button>
                        <button type="submit">Update report</button>
                    </div>
                </form>
            </div>
        </dialog>
    @endcan
    @include('admin.partials.modal-scripts')
@endsection
