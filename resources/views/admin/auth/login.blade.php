@extends('admin.layouts.app')

@section('title', 'Admin Login')
@section('body_class', 'admin-login-body')

@section('content')
    <main class="admin-login-page">
        <section class="admin-login-brand" aria-label="Admin introduction">
            <a class="admin-logo" href="{{ route('admin.login') }}">
                bdgigs<span>.</span>
            </a>
            <div>
                <p class="admin-eyebrow">Marketplace control center</p>
                <h1>Manage services, orders, users, and marketplace quality from one calm workspace.</h1>
                <p>
                    This Blade admin area is separate from the React storefront and dashboard. It is ready for future
                    real authentication, roles, charts, and CRUD modules.
                </p>
            </div>
            <dl class="admin-login-proof">
                <div>
                    <dt>2.4k</dt>
                    <dd>Active gigs</dd>
                </div>
                <div>
                    <dt>186</dt>
                    <dd>Orders today</dd>
                </div>
                <div>
                    <dt>98%</dt>
                    <dd>Response health</dd>
                </div>
            </dl>
        </section>

        <section class="admin-login-card" aria-labelledby="adminLoginTitle">
            <div>
                <p class="admin-eyebrow">Admin access</p>
                <h2 id="adminLoginTitle">Sign in to admin</h2>
                <p>Use the temporary template credentials, or set your own in the environment file.</p>
            </div>

            <form method="POST" action="{{ route('admin.login.submit') }}" class="admin-login-form">
                @csrf

                <label>
                    <span>Email address</span>
                    <input
                        type="email"
                        name="email"
                        value="{{ old('email', config('admin.email')) }}"
                        autocomplete="email"
                        required
                    >
                    @error('email')
                        <small>{{ $message }}</small>
                    @enderror
                </label>

                <label>
                    <span>Password</span>
                    <input
                        type="password"
                        name="password"
                        autocomplete="current-password"
                        placeholder="Enter admin password"
                        required
                    >
                    @error('password')
                        <small>{{ $message }}</small>
                    @enderror
                </label>

                <div class="admin-login-options">
                    <label>
                        <input type="checkbox" name="remember" value="1">
                        <span>Keep me signed in</span>
                    </label>
                    <a href="{{ route('admin.login') }}">Need help?</a>
                </div>

                <button type="submit">Sign in</button>
            </form>

            <p class="admin-demo-note">
                Demo: <strong>{{ config('admin.email') }}</strong> / <strong>{{ config('admin.password') }}</strong>
            </p>
        </section>
    </main>
@endsection
