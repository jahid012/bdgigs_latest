<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Email Preferences</title>
    @vite(['resources/css/app.css'])
</head>
<body>
    <main class="auth-status-page">
        <section class="auth-status-card">
            @if ($record && $user)
                <p class="auth-status-eyebrow">Email preferences</p>
                <h1>Manage marketing emails</h1>
                <p>Choose whether {{ $user->email }} receives marketing and engagement emails. Security and transactional emails stay enabled.</p>
                <form method="POST" action="{{ route('email.preferences.update', $token) }}">
                    @csrf
                    <input type="hidden" name="email_type" value="{{ $record->email_type ?: 'marketing' }}">
                    <label class="settings-check">
                        <input type="checkbox" name="enabled" value="1" @checked(! $user->marketing_unsubscribed_at)>
                        <span>Receive marketing and engagement emails</span>
                    </label>
                    <button class="btn btn-primary" type="submit">Save preference</button>
                </form>
            @else
                <p class="auth-status-eyebrow">Email preferences</p>
                <h1>Preference link expired</h1>
                <p>This preferences link is invalid or expired.</p>
            @endif
        </section>
    </main>
</body>
</html>
