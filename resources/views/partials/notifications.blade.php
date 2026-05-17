@php
    $notifications = [];
    $pushNotification = function (array $notification) use (&$notifications) {
        $message = trim((string) ($notification['message'] ?? ''));

        if ($message === '') {
            return;
        }

        $notifications[] = [
            'type' => $notification['type'] ?? 'info',
            'title' => $notification['title'] ?? null,
            'message' => $message,
            'duration' => $notification['duration'] ?? null,
            'action' => $notification['action'] ?? null,
        ];
    };

    $flashNotify = session('notify');

    if (is_array($flashNotify)) {
        if (array_key_exists('message', $flashNotify)) {
            $pushNotification($flashNotify);
        } else {
            foreach ($flashNotify as $notification) {
                if (is_array($notification)) {
                    $pushNotification($notification);
                }
            }
        }
    }

    foreach (['success', 'error', 'warning', 'info'] as $type) {
        if (session()->has($type)) {
            $pushNotification([
                'type' => $type,
                'message' => session($type),
            ]);
        }
    }

    if ($errors->any()) {
        $pushNotification([
            'type' => 'error',
            'title' => 'Please check the form',
            'message' => $errors->first(),
            'duration' => 7000,
        ]);
    }
@endphp

<div id="bdgigs-notify-root" class="bdgigs-toast-root" aria-live="polite" aria-atomic="false"></div>
<script id="bdgigs-notify-payload" type="application/json">@json($notifications)</script>
<script src="{{ asset('assets/shared/notify.js') }}" defer></script>
