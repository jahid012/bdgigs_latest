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
                <div><dt>Assigned to</dt><dd>{{ $report->assignedTo?->name ?? 'Unassigned' }}</dd></div>
                <div><dt>Resolved by</dt><dd>{{ $report->resolvedBy?->name ?? 'Not resolved' }}</dd></div>
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
                    <p>Status updates notify the reporter when appropriate.</p>
                </div>
            </div>
            @can('reports.manage')
                <form class="admin-detail-form" method="POST" action="{{ route('admin.moderation-reports.update', $report) }}">
                    @csrf
                    @method('PATCH')
                    <label>
                        <span>Status</span>
                        <select name="status">
                            @foreach (\App\Models\ModerationReport::STATUSES as $status)
                                <option value="{{ $status }}" @selected($report->status === $status)>{{ str($status)->title() }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span>Resolution note</span>
                        <textarea name="note" rows="4" placeholder="What action was taken?">{{ $report->resolution_note }}</textarea>
                    </label>
                    <button type="submit">Update report</button>
                </form>
            @else
                <p class="admin-empty-note">You can inspect reports but cannot update them.</p>
            @endcan
        </aside>
    </section>
@endsection
