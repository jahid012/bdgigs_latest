<?php

namespace App\Services;

use App\Models\VisitorPageView;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VisitorAnalyticsService
{
    private const BOT_SIGNATURES = [
        'bot',
        'crawler',
        'spider',
        'slurp',
        'bingpreview',
        'facebookexternalhit',
        'whatsapp',
        'telegrambot',
        'headlesschrome',
        'phantomjs',
        'lighthouse',
        'pagespeed',
        'curl',
        'wget',
        'python',
        'go-http-client',
        'httpclient',
        'monitor',
        'uptime',
        'validator',
    ];

    public function record(Request $request, array $payload): ?VisitorPageView
    {
        if ($this->isBot($request->userAgent())) {
            return null;
        }

        $sessionId = (string) $request->session()->getId();
        $visitorId = $payload['visitorId'] ?? $sessionId;

        return VisitorPageView::create([
            'user_id' => $request->user()?->id,
            'visitor_id' => $visitorId ?: null,
            'session_id' => $sessionId ? hash('sha256', $sessionId) : null,
            'path' => $this->normalizePath((string) $payload['path']),
            'page_title' => $payload['title'] ?? null,
            'referrer' => $payload['referrer'] ?? null,
            'user_agent' => $request->userAgent(),
            'ip_hash' => $this->hashIp($request),
            'is_bot' => false,
            'visited_at' => now(),
        ]);
    }

    public function isBot(?string $userAgent): bool
    {
        $agent = Str::lower(trim((string) $userAgent));

        if ($agent === '') {
            return true;
        }

        foreach (self::BOT_SIGNATURES as $signature) {
            if (str_contains($agent, $signature)) {
                return true;
            }
        }

        return ! preg_match('/mozilla|chrome|safari|firefox|edg|opr|iphone|android/', $agent);
    }

    private function normalizePath(string $path): string
    {
        $path = trim($path) ?: '/';

        if (! str_starts_with($path, '/')) {
            return '/';
        }

        return mb_substr($path, 0, 512);
    }

    private function hashIp(Request $request): ?string
    {
        $ip = $request->ip();

        return $ip ? hash_hmac('sha256', $ip, (string) config('app.key')) : null;
    }
}
