@extends('admin.layouts.panel')

@section('title', 'Suspicious Activity Details')

@section('panel')
    @include('admin.partials.stats', ['stats' => $stats])

    <section class="admin-detail-layout">
        <article class="admin-panel admin-detail-summary">
            <div class="admin-detail-head admin-order-detail-head">
                <div>
                    <div class="admin-detail-badges">
                        <span class="admin-status-badge {{ in_array($activity->severity, ['critical', 'high'], true) ? 'is-danger' : 'is-warn' }}">{{ str($activity->severity)->title() }}</span>
                        <span class="admin-status-badge status-progress">{{ str($activity->type)->replace('_', ' ')->title() }}</span>
                    </div>
                    <h2>{{ str($activity->type)->replace('_', ' ')->title() }}</h2>
                    <p>{{ $activity->description }}</p>
                </div>
            </div>
            <dl class="admin-user-detail-list admin-detail-list">
                <div><dt>User</dt><dd>{{ $activity->user?->name ?? 'Anonymous' }}</dd></div>
                <div><dt>Email</dt><dd>{{ $activity->user?->email ?? 'No user email' }}</dd></div>
                <div><dt>IP address</dt><dd>{{ $activity->ip_address ?: 'Unknown' }}</dd></div>
                <div><dt>User agent</dt><dd>{{ $activity->user_agent ?: 'Unknown' }}</dd></div>
                <div><dt>Reviewed by</dt><dd>{{ $activity->reviewer?->name ?? 'Not reviewed' }}</dd></div>
                <div><dt>Created</dt><dd>{{ $activity->created_at?->format('M j, Y g:i A') }}</dd></div>
            </dl>
            <div class="admin-user-detail-actions">
                <a href="{{ route('admin.suspicious-activities') }}">Back to security queue</a>
                @if ($activity->user)
                    <a href="{{ route('admin.users.show', $activity->user) }}">View user</a>
                @endif
            </div>
        </article>

        <aside class="admin-panel admin-detail-action-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Review signal</h2>
                    <p>Mark reviewed after deciding whether an admin action is needed.</p>
                </div>
            </div>
            @can('security.review')
                <form class="admin-detail-form" method="POST" action="{{ route('admin.suspicious-activities.review', $activity) }}">
                    @csrf
                    @method('PATCH')
                    <label>
                        <span>Severity</span>
                        <select name="severity">
                            @foreach (\App\Models\SuspiciousActivityLog::SEVERITIES as $severity)
                                <option value="{{ $severity }}" @selected($activity->severity === $severity)>{{ str($severity)->title() }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span>Review note</span>
                        <textarea name="description" rows="4">{{ $activity->description }}</textarea>
                    </label>
                    <button type="submit">Mark reviewed</button>
                </form>
            @else
                <p class="admin-empty-note">You can inspect this signal but cannot mark it reviewed.</p>
            @endcan
        </aside>
    </section>

    <section class="admin-panel">
        <div class="admin-panel-head">
            <div>
                <h2>Metadata</h2>
                <p>Rule-specific context stored with the signal.</p>
            </div>
        </div>
        <pre class="admin-code-block">{{ json_encode($activity->metadata ?: [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
    </section>
@endsection
