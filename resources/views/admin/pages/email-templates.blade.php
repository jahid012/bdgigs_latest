@extends('admin.layouts.panel')

@section('title', 'Email Templates')

@section('panel')
    @include('admin.partials.stats', ['stats' => $stats])

    <section class="admin-detail-layout">
        <article class="admin-panel admin-table-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Template library</h2>
                    <p>Use variables like <code>@{{ user_name }}</code>, <code>@{{ order_id }}</code>, and <code>@{{ action_url }}</code>.</p>
                </div>
            </div>

            <form class="admin-user-search-form" method="GET" action="{{ route('admin.email-templates') }}">
                <label>
                    <span>Search</span>
                    <input type="search" name="q" value="{{ $searchQuery }}" placeholder="Search key, name, or subject">
                </label>
                <label>
                    <span>Category</span>
                    <select name="category">
                        <option value="">All categories</option>
                        @foreach ($categories as $key => $label)
                            <option value="{{ $key }}" @selected($selectedCategory === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span>Status</span>
                    <select name="status">
                        <option value="">All statuses</option>
                        <option value="active" @selected($selectedStatus === 'active')>Active</option>
                        <option value="inactive" @selected($selectedStatus === 'inactive')>Inactive</option>
                    </select>
                </label>
                <button type="submit">Filter templates</button>
            </form>

            <div class="admin-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Template</th>
                            <th>Key</th>
                            <th>Category</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($templates as $template)
                            <tr class="admin-linked-row" data-href="{{ route('admin.email-templates.show', $template) }}" tabindex="0">
                                <td>
                                    <a class="admin-table-primary-link" href="{{ route('admin.email-templates.show', $template) }}">
                                        {{ $template->name }}
                                    </a>
                                </td>
                                <td><code>{{ $template->key }}</code></td>
                                <td>{{ $categories[$template->category] ?? $template->category }}</td>
                                <td>{{ Str::limit($template->subject, 72) }}</td>
                                <td>
                                    <span class="{{ $template->is_active ? 'status-completed' : 'status-cancelled' }}">
                                        {{ $template->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>{{ $template->updated_at?->format('M j, Y') }}</td>
                                <td>
                                    <a class="admin-table-action" href="{{ route('admin.email-templates.show', $template) }}">Details</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">No email templates found. Run the email template seeder to install defaults.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $templates->links() }}
        </article>

        <aside class="admin-panel admin-detail-action-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Create template</h2>
                    <p>Add a new event email without changing source code.</p>
                </div>
            </div>
            <form class="admin-detail-form" method="POST" action="{{ route('admin.email-templates.store') }}">
                @csrf
                @include('admin.pages.partials.email-template-fields', ['template' => null, 'categories' => $categories, 'defaultVariables' => $defaultVariables])
                <button type="submit">Create template</button>
            </form>

            <section class="admin-email-help-panel">
                <h2>Template workflow</h2>
                <p>Open a row to edit copy, send a test email, reset the default copy, or inspect logs for that exact template.</p>
            </section>
        </aside>
    </section>

    <script>
        document.querySelectorAll(".admin-linked-row").forEach((row) => {
            row.addEventListener("click", (event) => {
                if (event.target.closest("a, button, input, select, textarea")) {
                    return;
                }

                window.location.href = row.dataset.href;
            });
            row.addEventListener("keydown", (event) => {
                if (event.key !== "Enter" && event.key !== " ") {
                    return;
                }

                event.preventDefault();
                window.location.href = row.dataset.href;
            });
        });
    </script>
@endsection
