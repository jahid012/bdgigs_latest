<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    @vite(['resources/css/app.css'])
</head>
<body>
    <main class="auth-status-page">
        <section class="auth-status-card">
            <p class="auth-status-eyebrow">{{ $success ? 'Updated' : 'Unavailable' }}</p>
            <h1>{{ $title }}</h1>
            <p>{{ $message }}</p>
            <a class="btn btn-primary" href="/">Back to bdgigs</a>
        </section>
    </main>
</body>
</html>
