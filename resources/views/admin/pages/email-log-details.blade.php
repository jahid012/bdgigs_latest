@extends('admin.layouts.panel')

@section('title', 'Email Log Details')

@section('panel')
    @include('admin.partials.stats', ['stats' => $stats])

    <section class="admin-detail-layout">
        <article class="admin-panel admin-detail-summary">
            <div class="admin-detail-head">
                <div>
                    <div class="admin-detail-badges">
                        <span class="admin-status-badge {{ $emailLog->status === 'sent' ? 'is-good' : ($emailLog->status === 'failed' ? 'is-danger' : 'is-warn') }}">{{ str($emailLog->status)->title() }}</span>
                    </div>
                    <h2>{{ $emailLog->subject }}</h2>
                    <p>{{ $emailLog->recipient_email }} - {{ $emailLog->created_at?->format('M j, Y g:i A') }}</p>
                </div>
            </div>
            <dl class="admin-user-detail-list admin-detail-list">
                <div><dt>User</dt><dd>{{ $emailLog->user?->name ?? 'No linked user' }}</dd></div>
                <div><dt>Template</dt><dd>{{ $emailLog->email_template_key ?: 'Raw email' }}</dd></div>
                <div><dt>Status</dt><dd>{{ str($emailLog->status)->title() }}</dd></div>
                <div><dt>Error</dt><dd>{{ $emailLog->error_message ?: 'No error recorded' }}</dd></div>
            </dl>
            <div class="admin-user-detail-actions">
                <a href="{{ route('admin.email-logs') }}">Back to logs</a>
                @if ($template)
                    <a href="{{ route('admin.email-templates.show', $template) }}">Open template</a>
                @endif
                @if ($emailLog->status === 'failed')
                    <form method="POST" action="{{ route('admin.email-logs.retry', $emailLog) }}">
                        @csrf
                        <button type="submit">Retry failed email</button>
                    </form>
                @endif
            </div>
        </article>

        <aside class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Rendered preview</h2>
                    <p>Preview is rendered with the logged payload and current template copy.</p>
                </div>
            </div>
            @if ($preview['html'])
                <iframe class="admin-email-preview-frame" title="Rendered email preview" srcdoc="{{ e($preview['html']) }}"></iframe>
            @else
                <p class="admin-empty-note">No rendered preview is available for this raw email.</p>
            @endif
        </aside>
    </section>

    <section class="admin-detail-grid">
        <article class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Payload</h2>
                    <p>Data used for template variables when this email was attempted.</p>
                </div>
            </div>
            <pre class="admin-code-block">{{ json_encode($emailLog->payload ?: [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </article>

        <article class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Text preview</h2>
                    <p>Plain text fallback rendered from the template.</p>
                </div>
            </div>
            <pre class="admin-code-block">{{ $preview['text'] ?: 'No text preview available.' }}</pre>
        </article>
    </section>
@endsection
