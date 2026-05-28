<?php

namespace App\Services;

use App\Events\NewDeviceLoginDetected;
use App\Models\User;
use App\Models\UserLoginDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LoginSecurityService
{
    public function __construct(private readonly CountryDetectorService $countries)
    {
    }

    public function inspect(User $user, Request $request): void
    {
        if (! appSetting('login_security_alerts_enabled', true)) {
            return;
        }

        $userAgent = (string) $request->userAgent();
        $ipAddress = $request->ip();
        $location = $this->countries->detect($request);
        $deviceHash = hash('sha256', $userAgent ?: 'unknown-browser');
        $knownDevices = $user->loginDevices()->exists();

        $device = UserLoginDevice::firstOrNew([
            'user_id' => $user->id,
            'device_hash' => $deviceHash,
        ]);

        $isNewDevice = ! $device->exists;
        $isNewIp = $device->exists && filled($ipAddress) && $device->ip_address !== $ipAddress;
        $shouldAlert = $knownDevices
            && ($isNewDevice || $isNewIp)
            && (! $device->last_alerted_at || $device->last_alerted_at->lt(now()->subDay()));

        $device->fill([
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'browser' => $this->browserLabel($userAgent),
            'device' => $this->deviceLabel($userAgent),
            'location' => $location,
            'first_seen_at' => $device->first_seen_at ?: now(),
            'last_seen_at' => now(),
            'last_alerted_at' => $shouldAlert ? now() : $device->last_alerted_at,
            'metadata' => [
                'reason' => $isNewDevice ? 'new_device' : ($isNewIp ? 'new_ip' : 'known_login'),
            ],
        ])->save();

        if ($shouldAlert) {
            event(new NewDeviceLoginDetected($user, $device->fresh(), [
                'reason' => $isNewDevice ? 'new_device' : 'new_ip',
                'ip_address' => $ipAddress,
                'browser' => $device->browser,
                'device' => $device->device,
                'location' => $location ?: 'Unknown',
                'login_time' => now()->format('M j, Y g:i A T'),
            ]));
        }
    }

    private function browserLabel(string $userAgent): string
    {
        return match (true) {
            str_contains($userAgent, 'Edg/') => 'Microsoft Edge',
            str_contains($userAgent, 'Chrome/') => 'Chrome',
            str_contains($userAgent, 'Firefox/') => 'Firefox',
            str_contains($userAgent, 'Safari/') => 'Safari',
            default => 'Unknown browser',
        };
    }

    private function deviceLabel(string $userAgent): string
    {
        return match (true) {
            Str::contains($userAgent, ['iPhone', 'Android', 'Mobile']) => 'Mobile device',
            Str::contains($userAgent, ['iPad', 'Tablet']) => 'Tablet',
            Str::contains($userAgent, ['Windows', 'Macintosh', 'Linux']) => 'Desktop device',
            default => 'Unknown device',
        };
    }
}
