@extends('admin.layouts.panel')

@section('title', 'Email Logs')

@section('panel')
    @include('admin.partials.stats', ['stats' => $stats])

    <section class="admin-page-grid">
        <article class="admin-panel admin-table-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Delivery attempts</h2>
                    <p>Filter by status, template, recipient, user, and send date.</p>
                </div>
            </div>

            <form class="admin-user-filter-form" method="GET" action="{{ route('admin.email-logs') }}">
                <label>
                    <span>Search</span>
                    <input type="search" name="q" value="{{ $filters['search'] }}" placeholder="Recipient, subject, or template">
                </label>
                <label>
                    <span>Status</span>
                    <select name="status">
                        <option value="">Any status</option>
                        @foreach (['pending', 'sent', 'failed'] as $status)
                            <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ str($status)->title() }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span>Template</span>
                    <select name="template">
                        <option value="">Any template</option>
                        @foreach ($templates as $template)
                            <option value="{{ $template->key }}" @selected($filters['template'] === $template->key)>{{ $template->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span>User</span>
                    <input type="search" name="user" value="{{ $filters['user'] }}" placeholder="Name or account email">
                </label>
                <label>
                    <span>From</span>
                    <input type="date" name="from" value="{{ $filters['dateFrom'] }}">
                </label>
                <label>
                    <span>To</span>
                    <input type="date" name="to" value="{{ $filters['dateTo'] }}">
                </label>
                <div>
                    <button type="submit">Apply filters</button>
                    <a href="{{ route('admin.email-logs') }}">Clear</a>
                </div>
            </form>

            <div class="admin-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Recipient</th>
                            <th>Template</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Sent</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr>
                                <td>
                                    <strong>{{ $log->recipient_email }}</strong>
                                    <small>{{ $log->user?->name ?? 'No linked user' }}</small>
                                </td>
                                <td>{{ $log->email_template_key ?: 'Raw email' }}</td>
                                <td>{{ $log->subject }}</td>
                                <td><span class="admin-status-badge {{ $log->status === 'sent' ? 'is-good' : ($log->status === 'failed' ? 'is-danger' : 'is-warn') }}">{{ str($log->status)->title() }}</span></td>
                                <td>{{ $log->sent_at?->format('M j, Y g:i A') ?? $log->created_at?->format('M j, Y g:i A') }}</td>
                                <td>
                                    <a class="admin-panel-link" href="{{ route('admin.email-logs.show', $log) }}">Open log</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6">No email logs matched these filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $logs->links() }}
        </article>
    </section>
@endsection
