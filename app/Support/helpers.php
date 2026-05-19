<?php

use App\Support\PlatformSettings;
use Illuminate\Http\RedirectResponse;

if (! function_exists('notifyFlash')) {
    function notifyFlash(string $type, string $message, ?string $title = null, array $options = []): void
    {
        $notification = array_merge($options, [
            'type' => $type,
            'title' => $title,
            'message' => $message,
        ]);

        $notifications = session()->get('notify', []);

        if (isset($notifications['message'])) {
            $notifications = [$notifications];
        }

        $notifications[] = array_filter($notification, fn ($value) => $value !== null);

        session()->flash('notify', $notifications);
    }
}

if (! function_exists('redirectWithNotify')) {
    function redirectWithNotify(
        string $route,
        string $type,
        string $message,
        ?string $title = null,
        array $parameters = [],
        array $options = []
    ): RedirectResponse {
        return redirect()
            ->route($route, $parameters)
            ->withNotify($type, $message, $title, $options);
    }
}

if (! function_exists('backWithNotify')) {
    function backWithNotify(
        string $type,
        string $message,
        ?string $title = null,
        array $options = []
    ): RedirectResponse {
        return back()->withNotify($type, $message, $title, $options);
    }
}

if (! function_exists('appSetting')) {
    function appSetting(string $key, mixed $default = null): mixed
    {
        return PlatformSettings::get($key, $default);
    }
}

if (! function_exists('platformSetting')) {
    function platformSetting(string $key, mixed $default = null): mixed
    {
        return PlatformSettings::get($key, $default);
    }
}

if (! function_exists('platformSettings')) {
    function platformSettings(?string $group = null): array
    {
        return $group ? PlatformSettings::group($group) : PlatformSettings::all();
    }
}

if (! function_exists('setPlatformSetting')) {
    function setPlatformSetting(string $key, mixed $value): void
    {
        PlatformSettings::set($key, $value);
    }
}

if (! function_exists('clearPlatformSettingsCache')) {
    function clearPlatformSettingsCache(): void
    {
        PlatformSettings::clearCache();
    }
}
