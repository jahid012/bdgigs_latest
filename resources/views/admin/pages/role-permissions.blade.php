@extends('admin.layouts.panel')

@section('title', 'Assign Permissions')

@section('panel')
    <section class="admin-access-grid">
        <aside class="admin-panel admin-role-list-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Select role</h2>
                    <p>Switch between roles without leaving this editor.</p>
                </div>
            </div>

            <div class="admin-role-list">
                @foreach ($roles as $role)
                    <a
                        class="admin-role-card {{ ($selectedRole['id'] ?? null) === $role['id'] ? 'is-active' : '' }}"
                        href="{{ route('admin.roles.permissions', $role['id']) }}"
                    >
                        <span>
                            <strong>{{ $role['label'] }}</strong>
                            <small>{{ $role['permission_count'] }} permissions</small>
                        </span>
                        <em>{{ $role['users_count'] }} users</em>
                    </a>
                @endforeach
            </div>

            <a class="admin-panel-link" href="{{ route('admin.roles') }}">Back to roles</a>
        </aside>

        <article class="admin-panel admin-permission-panel">
            @if ($selectedRole)
                <form id="adminRolePermissionsForm" method="POST" action="{{ route('admin.roles.permissions.update', $selectedRole['id']) }}">
                    @csrf
                </form>
            @endif

            <div class="admin-panel-head">
                <div>
                    <h2>{{ $selectedRole['label'] ?? 'Role permissions' }}</h2>
                    <p>{{ $selectedRole['description'] ?? 'Select a role to review assigned permissions.' }}</p>
                </div>
                @if ($selectedRole)
                    <button type="submit" form="adminRolePermissionsForm">Save permissions</button>
                @endif
            </div>

            @if ($selectedRole)
                <div class="admin-role-summary">
                    <span><b>{{ $selectedRole['users_count'] }}</b>Assigned users</span>
                    <span><b>{{ $selectedRole['permission_count'] }}</b>Granted permissions</span>
                    <span><b>{{ $selectedRole['name'] }}</b>System key</span>
                </div>
            @endif

            <div class="admin-permission-groups">
                @foreach ($permissionGroups as $group)
                    <section class="admin-permission-group">
                        <div class="admin-permission-group-head">
                            <strong>{{ $group['label'] }}</strong>
                            <span>{{ count($group['permissions']) }} permissions</span>
                        </div>

                        <div class="admin-permission-list">
                            @foreach ($group['permissions'] as $permission)
                                <label class="admin-permission-chip {{ $permission['assigned'] ? 'is-on' : '' }} {{ $permission['sensitive'] ? 'is-sensitive' : '' }}">
                                    <input
                                        form="adminRolePermissionsForm"
                                        type="checkbox"
                                        name="permissions[]"
                                        value="{{ $permission['name'] }}"
                                        @checked($permission['assigned'])
                                        @disabled(($selectedRole['name'] ?? null) === 'super_admin')
                                    >
                                    <span>
                                        <b>{{ $permission['label'] }}</b>
                                        <small>{{ $permission['name'] }}</small>
                                    </span>
                                    @if ($permission['sensitive'])
                                        <em>Sensitive</em>
                                    @endif
                                </label>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>

            @if (($selectedRole['name'] ?? null) === 'super_admin')
                <p class="admin-empty-note">Super Admin always keeps every permission. Create a narrower role for day-to-day staff access.</p>
            @endif
        </article>
    </section>
@endsection
