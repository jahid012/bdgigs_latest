<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $inactiveReason = $request->user()?->suspended_at ? 'suspended' : ($request->user()?->deactivated_at ? 'deactivated' : null);

        if ($inactiveReason) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            abort(423, 'This account is '.$inactiveReason.'.');
        }

        return $next($request);
    }
}
