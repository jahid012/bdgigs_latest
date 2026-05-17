@extends('admin.layouts.panel')

@section('title', 'Access Control')

@section('panel')
    @include('admin.partials.stats', ['stats' => $stats])

    <section class="admin-access-grid">
        <article class="admin-panel admin-role-list-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Staff roles</h2>
                    <p>Create roles here, then edit permissions on a focused page.</p>
                </div>
            </div>

            <form class="admin-create-role-form" method="POST" action="{{ route('admin.roles.store') }}">
                @csrf
                <input type="hidden" name="include_admin_access" value="1">
                <label>
                    <span>New role name</span>
                    <input type="text" name="label" value="{{ old('label') }}" placeholder="Risk reviewer">
                </label>
                <button type="submit">Create role</button>
            </form>

            <div class="admin-role-list">
                @forelse ($roles as $role)
                    <article class="admin-role-card">
                        <span>
                            <strong>{{ $role['label'] }}</strong>
                            <small>{{ $role['description'] }}</small>
                        </span>
                        <em>{{ $role['users_count'] }} users</em>
                        <div class="admin-role-card-actions">
                            <a href="{{ route('admin.roles.permissions', $role['id']) }}">Edit permissions</a>
                        </div>
                    </article>
                @empty
                    <p class="admin-empty-note">No roles are seeded yet. Run the database seeder to populate access roles.</p>
                @endforelse
            </div>
        </article>

        <aside class="admin-panel admin-access-task-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Find users</h2>
                    <p>Search existing users and assign roles from a dedicated page.</p>
                </div>
            </div>

            <form class="admin-user-finder" method="GET" action="{{ route('admin.roles.users') }}">
                <label>
                    <span>Name or email</span>
                    <input type="search" name="q" placeholder="hasan@example.com">
                </label>
                <button type="submit">Find users</button>
            </form>

            <div class="admin-access-actions">
                <a class="admin-panel-link" href="{{ route('admin.roles.users') }}">Assign roles to users</a>
                @if ($roles->first())
                    <a class="admin-panel-link" href="{{ route('admin.roles.permissions', $roles->first()['id']) }}">Assign permissions to role</a>
                @endif
            </div>
        </aside>
    </section>

    <section class="admin-workflow-grid">
        <article class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Roles vs seller levels</h2>
                    <p>Keep staff security and marketplace status separate.</p>
                </div>
            </div>
            <div class="admin-workflow-steps">
                @foreach ($levelGuidance as $item)
                    <span><b>{{ substr($item['label'], 0, 1) }}</b><strong>{{ $item['label'] }}</strong><small>{{ $item['description'] }}</small></span>
                @endforeach
            </div>
        </article>

        <article class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Sensitive controls</h2>
                    <p>These permissions should stay limited to trusted staff.</p>
                </div>
            </div>
            <div class="admin-card-list compact">
                @foreach ($sensitivePermissions as $permission)
                    <article class="admin-mini-card">
                        <div>
                            <strong>{{ $permission }}</strong>
                            <p>Requires additional operational trust.</p>
                        </div>
                        <b>Review</b>
                    </article>
                @endforeach
            </div>
        </article>
    </section>
@endsection
