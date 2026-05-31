<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CountryDetectorService
{
    public function detect(Request $request): ?string
    {
        $headerCountry = $this->fromHeaders($request);

        if ($headerCountry) {
            return $headerCountry;
        }

        $ip = $request->ip();

        if (! $this->isPublicIp($ip)) {
            return null;
        }

        return Cache::remember(
            'ipinfo-country:'.sha1($ip),
            now()->addDay(),
            fn () => $this->fromIpinfo($ip),
        );
    }

    private function fromHeaders(Request $request): ?string
    {
        foreach ([
            'CF-IPCountry',
            'X-Vercel-IP-Country',
            'CloudFront-Viewer-Country',
            'X-Appengine-Country',
        ] as $header) {
            $country = trim((string) $request->headers->get($header));

            if ($country && strtoupper($country) !== 'XX') {
                return $country;
            }
        }

        return null;
    }

    private function fromIpinfo(string $ip): ?string
    {
        $token = config('services.ipinfo.token');

        if (! $token) {
            return null;
        }

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(2)
                ->get(rtrim(config('services.ipinfo.endpoint'), '/')."/{$ip}");
        } catch (ConnectionException) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $payload = $response->json();

        return $payload['country_name']
            ?? $payload['country']
            ?? $payload['country_code']
            ?? null;
    }

    private function isPublicIp(?string $ip): bool
    {
        return filled($ip)
            && filter_var(
                $ip,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
            ) !== false;
    }
}
