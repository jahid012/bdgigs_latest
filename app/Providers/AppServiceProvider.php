<?php

namespace App\Providers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (method_exists(Auth::guard('web'), 'setRememberDuration')) {
            Auth::guard('web')->setRememberDuration(60 * 24 * 30);
        }

        RedirectResponse::macro('withNotify', function ($type = 'info', ?string $message = null, ?string $title = null, array $options = []) {
            $notification = is_array($type)
                ? $type
                : array_merge($options, [
                    'type' => $type,
                    'title' => $title,
                    'message' => $message,
                ]);

            $notification['type'] = $notification['type'] ?? 'info';
            $notification['message'] = trim((string) ($notification['message'] ?? ''));

            if ($notification['message'] === '') {
                return $this;
            }

            $notifications = session()->get('notify', []);

            if (isset($notifications['message'])) {
                $notifications = [$notifications];
            }

            $notifications[] = array_filter($notification, fn ($value) => $value !== null);

            return $this->with('notify', $notifications);
        });
    }
}
