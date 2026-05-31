@extends('admin.layouts.panel')

@section('title', 'Assign Roles to Admins')

@section('panel')
    <section class="admin-panel admin-user-access-panel">
        <div class="admin-panel-head">
            <div>
                <h2>Find admins</h2>
                <p>Search by name or email, then assign roles from the admin row.</p>
            </div>
            <a class="admin-panel-link" href="{{ route('admin.roles') }}">Back to roles</a>
        </div>

        <form class="admin-user-search-form" method="GET" action="{{ route('admin.roles.users') }}">
            <label>
                <span>Admin search</span>
                <input type="search" name="q" value="{{ $searchQuery }}" placeholder="Search name or email">
            </label>
            <label>
                <span>Role filter</span>
                <select name="role">
                    <option value="">All roles</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role['name'] }}" @selected($roleFilter === $role['name'])>{{ $role['label'] }}</option>
                    @endforeach
                </select>
            </label>
            <button type="submit">Search users</button>
            @if ($searchQuery !== '' || $roleFilter !== '')
                <a href="{{ route('admin.roles.users') }}">Clear</a>
            @endif
        </form>

        <div class="admin-user-access-list">
            @forelse ($assignableUsers as $user)
                <form id="user-access-{{ $user['id'] }}" class="admin-user-access-card" method="POST" action="{{ route('admin.users.roles.update', $user['id']) }}">
                    @csrf
                    <div class="admin-user-access-identity">
                        <strong>{{ $user['name'] }}</strong>
                        <span>{{ $user['email'] }}</span>
                    <small>{{ $user['can_admin'] ? 'Can access admin panel' : 'No admin access yet' }}</small>
                    </div>

                    <div class="admin-user-role-select">
                        <span>Assigned roles</span>
                        <div class="admin-user-role-checks">
                            @foreach ($roles as $role)
                                <label>
                                    <input type="checkbox" name="roles[]" value="{{ $role['name'] }}" @checked(in_array($role['name'], $user['roles'], true))>
                                    {{ $role['label'] }}
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="admin-user-role-meta">
                        @forelse ($user['role_labels'] as $roleLabel)
                            <b>{{ $roleLabel }}</b>
                        @empty
                            <em>No roles assigned</em>
                        @endforelse
                    </div>

                    <button type="submit">Update roles</button>
                </form>
            @empty
                <p class="admin-empty-note">No users matched your search.</p>
            @endforelse
        </div>

        @include('admin.partials.pagination', ['pagination' => $pagination, 'label' => 'User role assignment pagination'])
    </section>
@endsection
