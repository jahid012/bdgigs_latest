@extends('admin.layouts.app')

@section('body_class', 'admin-dashboard-body')

@section('content')
    @php
        $adminUser = session('admin_user', ['name' => config('admin.name'), 'email' => config('admin.email')]);
        $navItems = [
            ['label' => 'Overview', 'route' => 'admin.dashboard'],
            ['label' => 'Users', 'route' => 'admin.users'],
            ['label' => 'Gigs', 'route' => 'admin.gigs'],
            ['label' => 'Orders', 'route' => 'admin.orders'],
            ['label' => 'Payments', 'route' => 'admin.payments'],
            ['label' => 'Disputes', 'route' => 'admin.disputes'],
            ['label' => 'Reports', 'route' => 'admin.reports'],
            ['label' => 'Settings', 'route' => 'admin.settings'],
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
                    <a class="{{ request()->routeIs($item['route']) ? 'is-active' : '' }}" href="{{ route($item['route']) }}">
                        <span></span>
                        {{ $item['label'] }}
                    </a>
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
                    <label class="admin-search">
                        <span class="sr-only">Search admin</span>
                        <input type="search" placeholder="{{ $searchPlaceholder ?? 'Search admin' }}">
                    </label>
                    <div class="admin-user-chip">
                        <span>{{ strtoupper(substr($adminUser['name'], 0, 1)) }}</span>
                        <div>
                            <strong>{{ $adminUser['name'] }}</strong>
                            <small>{{ $adminUser['email'] }}</small>
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
                            <a href="{{ route($action['route']) }}">
                                <span>{{ $action['label'] }}</span>
                                <small>{{ $action['meta'] }}</small>
                            </a>
                        @endforeach
                    </nav>
                </section>
            @endisset

            @yield('panel')
        </main>
    </div>
@endsection
