<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta
      name="description"
      content="bdgigs is a modern freelancing marketplace for hiring expert creative, technical, and marketing talent."
    >
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap"
      rel="stylesheet"
    >
    <title>bdgigs | Freelance Services Marketplace</title>
    <link rel="stylesheet" href="{{ asset('assets/shared/notify.css') }}">
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/main.jsx'])
  </head>
  <body>
    @if (session('admin_impersonator_id'))
      <aside class="marketplace-impersonation-bar" aria-label="Admin impersonation session">
        <div>
          <strong>Admin preview active</strong>
          <span>You are viewing bdgigs as {{ auth()->user()?->name ?: 'this user' }}.</span>
        </div>
        <form method="POST" action="{{ route('admin.impersonation.stop') }}">
          @csrf
          <button type="submit">Return to admin</button>
        </form>
      </aside>
    @endif
    <div id="root"></div>
    @include('partials.notifications')
  </body>
</html>
