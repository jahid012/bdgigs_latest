@extends('admin.layouts.app')

@section('body_class', 'admin-dashboard-body')

@section('content')
    @php
        $adminUser = auth()->user();
        $navItems = [
            ['label' => 'Overview', 'route' => 'admin.dashboard', 'permission' => 'admin.access'],
            ['label' => 'Users', 'route' => 'admin.users', 'permission' => 'users.view'],
            ['label' => 'Gigs', 'route' => 'admin.gigs', 'permission' => 'gigs.view'],
            ['label' => 'Orders', 'route' => 'admin.orders', 'permission' => 'orders.view'],
            ['label' => 'Payments', 'route' => 'admin.payments', 'permission' => 'payments.view'],
            ['label' => 'Disputes', 'route' => 'admin.disputes', 'permission' => 'disputes.view'],
            ['label' => 'Reports', 'route' => 'admin.reports', 'permission' => 'reports.view'],
            ['label' => 'Settings', 'route' => 'admin.settings', 'permission' => 'settings.view'],
            ['label' => 'Access Control', 'route' => 'admin.roles', 'permission' => 'roles.manage'],
        ];
    @endphp

    <div class="admin-shell">
        <aside class="admin-sidebar">
            <div class="admin-sidebar-brand">
                <a class="admin-logo" href="{{ route('admin.dashboard') }}">bdgigs<span>.</span></a>
                <small>Admin Panel</small>
            </div>

            <nav aria-label="Admin navigation">
                @foreach ($navItems as $item)
                    @can($item['permission'])
                        @php
                            $isActive = request()->routeIs($item['route'])
                                || ($item['route'] === 'admin.roles' && request()->routeIs('admin.roles.*'));
                        @endphp
                        <a class="{{ $isActive ? 'is-active' : '' }}" href="{{ route($item['route']) }}">
                            <span></span>
                            {{ $item['label'] }}
                        </a>
                    @endcan
                @endforeach
            </nav>

            <section class="admin-sidebar-card">
                <strong>Operations health</strong>
                <p>Use this panel to review marketplace signals, act on risk, and keep service quality high.</p>
                <div>
                    @foreach (($healthSummary ?? []) as $item)
                        <span><b>{{ $item['value'] }}</b>{{ $item['label'] }}</span>
                    @endforeach
                </div>
            </section>
        </aside>

        <main class="admin-main">
            <header class="admin-topbar">
                <div>
                    <p class="admin-eyebrow">{{ $pageEyebrow ?? 'Admin panel' }}</p>
                    <h1>{{ $pageTitle ?? 'Admin panel' }}</h1>
                    @isset($pageDescription)
                        <p class="admin-page-description">{{ $pageDescription }}</p>
                    @endisset
                </div>
                <div class="admin-topbar-actions">
                    <form class="admin-search" method="GET" action="{{ url()->current() }}">
                        @foreach (request()->except(['q', 'page']) as $name => $value)
                            @if (is_scalar($value))
                                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                            @endif
                        @endforeach
                        <label>
                            <span class="sr-only">Search admin</span>
                            <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ $searchPlaceholder ?? 'Search admin' }}">
                        </label>
                    </form>
                    <div class="admin-user-chip">
                        <span>{{ strtoupper(substr($adminUser?->name ?? config('admin.name'), 0, 1)) }}</span>
                        <div>
                            <strong>{{ $adminUser?->name ?? config('admin.name') }}</strong>
                            <small>{{ $adminUser?->email ?? config('admin.email') }}</small>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="admin-ghost-button">Log out</button>
                    </form>
                </div>
            </header>

            @isset($pageActions)
                <section class="admin-command-bar" aria-label="Admin quick actions">
                    <div>
                        <strong>Quick actions</strong>
                        <span>Common workflows for this area</span>
                    </div>
                    <nav>
                        @foreach ($pageActions as $action)
                            @php
                                $actionPermission = $action['permission'] ?? collect($navItems)->firstWhere('route', $action['route'])['permission'] ?? null;
                            @endphp
                            @if (! $actionPermission || auth()->user()?->can($actionPermission))
                                <a href="{{ route($action['route']) }}">
                                    <span>{{ $action['label'] }}</span>
                                    <small>{{ $action['meta'] }}</small>
                                </a>
                            @endif
                        @endforeach
                    </nav>
                </section>
            @endisset

            @yield('panel')
        </main>
    </div>
@endsection
