@extends('admin.layouts.panel')

@section('title', $template->name)

@section('panel')
    @include('admin.partials.stats', ['stats' => $stats])

    <section class="admin-detail-layout">
        <article class="admin-panel admin-table-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Edit template</h2>
                    <p>
                        <a class="admin-muted-link" href="{{ route('admin.email-templates') }}">Email Templates</a>
                        <span>/</span>
                        <code>{{ $template->key }}</code>
                    </p>
                </div>
                <a class="admin-panel-link" href="{{ route('admin.email-templates.preview', $template) }}" target="_blank" rel="noreferrer">Preview email</a>
            </div>

            <form class="admin-detail-form admin-email-template-form" method="POST" action="{{ route('admin.email-templates.update', $template) }}">
                @csrf
                @method('PATCH')
                @include('admin.pages.partials.email-template-fields', ['template' => $template, 'categories' => $categories, 'defaultVariables' => $defaultVariables])
                <div>
                    <button type="submit">Save template</button>
                    <button type="submit" form="reset-email-template-{{ $template->id }}">Reset default</button>
                </div>
            </form>

            <form id="reset-email-template-{{ $template->id }}" method="POST" action="{{ route('admin.email-templates.reset', $template) }}">
                @csrf
            </form>
        </article>

        <aside class="admin-panel admin-detail-action-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Template details</h2>
                    <p>Send a test and review recent delivery attempts.</p>
                </div>
            </div>

            <dl class="admin-email-template-meta">
                <div>
                    <dt>Key</dt>
                    <dd>{{ $template->key }}</dd>
                </div>
                <div>
                    <dt>Category</dt>
                    <dd>{{ $categories[$template->category] ?? $template->category }}</dd>
                </div>
                <div>
                    <dt>Updated</dt>
                    <dd>{{ $template->updated_at?->format('M j, Y g:i A') }}</dd>
                </div>
            </dl>

            <form class="admin-inline-test-form" method="POST" action="{{ route('admin.email-templates.test', $template) }}">
                @csrf
                <label>
                    <span>Send test to</span>
                    <input type="email" name="email" value="{{ auth()->user()->email }}" required>
                </label>
                <button type="submit">Send test email</button>
            </form>

            <section class="admin-variable-list">
                <h2>Available variables</h2>
                <div>
                    @foreach (($template->available_variables ?: $defaultVariables) as $variable)
                        <code>{!! '&#123;&#123;'.e($variable).'&#125;&#125;' !!}</code>
                    @endforeach
                </div>
            </section>

            <div class="admin-email-log-panel">
                <h2>Template logs</h2>
                <p>Recent sent and failed emails for this template only.</p>
                @forelse ($logs as $log)
                    <article>
                        <strong>{{ $log->subject }}</strong>
                        <span>{{ $log->recipient_email }}</span>
                        <small>{{ strtoupper($log->status) }} - {{ $log->created_at->diffForHumans() }}</small>
                        @if ($log->error_message)
                            <em>{{ $log->error_message }}</em>
                        @endif
                    </article>
                @empty
                    <p class="admin-empty-note">No email logs for this template yet.</p>
                @endforelse
            </div>
        </aside>
    </section>
@endsection
