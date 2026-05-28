<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Unsubscribe</title>
    @vite(['resources/css/app.css'])
</head>
<body>
    <main class="auth-status-page">
        <section class="auth-status-card">
            @if ($record)
                <p class="auth-status-eyebrow">Unsubscribe</p>
                <h1>Stop marketing emails?</h1>
                <p>You will stop receiving marketing and engagement emails. Transactional order, payment, security, and account emails will still be sent.</p>
                <form method="POST" action="{{ route('email.unsubscribe.confirm', $token) }}">
                    @csrf
                    <button class="btn btn-primary" type="submit">Confirm unsubscribe</button>
                </form>
            @else
                <p class="auth-status-eyebrow">Unsubscribe</p>
                <h1>Link expired</h1>
                <p>This unsubscribe link is invalid or expired.</p>
            @endif
        </section>
    </main>
</body>
</html>
