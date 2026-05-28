<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CountryDetectorService;
use App\Services\SuspiciousActivityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'data' => $user ? [
                'id' => (string) $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'country' => $user->country,
                'initials' => initialsFromName($user->name),
                'online' => true,
                'role' => 'buyer',
                'sellerEnabled' => true,
                'sellerStatus' => $user->seller_status ?: 'not_applied',
                'twoFactorEnabled' => filled($user->two_factor_secret),
                'emailVerified' => $user->hasVerifiedEmail(),
                'emailVerifiedAt' => $user->email_verified_at?->toISOString(),
                'verificationStatus' => $user->verification_status,
                'accountStatus' => $user->suspended_at
                    ? 'suspended'
                    : ($user->deactivated_at ? 'deactivated' : 'active'),
                'authenticated' => true,
                'csrfToken' => csrf_token(),
            ] : [
                'authenticated' => false,
                'csrfToken' => csrf_token(),
            ],
        ]);
    }

    public function login(Request $request, SuspiciousActivityService $suspicious): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ]);
        $remember = (bool) ($credentials['remember'] ?? false);
        $loginCredentials = [
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ];
        $user = User::where('email', $loginCredentials['email'])->first();

        if ($user
            && Hash::check($loginCredentials['password'], $user->password)
            && ! $user->deactivated_at
            && ! $user->suspended_at
            && filled($user->two_factor_secret)) {
            return response()->json([
                'data' => [
                    'twoFactorRequired' => true,
                    'message' => 'Use the two factor login challenge to continue.',
                ],
            ], 409);
        }

        if (! Auth::attempt($loginCredentials, $remember)) {
            $key = 'failed-login:'.sha1(strtolower($loginCredentials['email']).'|'.$request->ip());
            $attempts = (int) Cache::increment($key);
            Cache::put($key, $attempts, now()->addHour());
            $suspicious->detectFailedLogin($loginCredentials['email'], $request, $attempts);

            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        Cache::forget('failed-login:'.sha1(strtolower($loginCredentials['email']).'|'.$request->ip()));

        if ($request->user()->deactivated_at || $request->user()->suspended_at) {
            Auth::guard('web')->logout();

            throw ValidationException::withMessages([
                'email' => [$request->user()->suspended_at ? 'This account is suspended.' : 'This account is deactivated.'],
            ]);
        }

        $request->session()->regenerate();

        return $this->me($request);
    }

    public function register(
        Request $request,
        CountryDetectorService $countries
    ): JsonResponse
    {
        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $payload['name'],
            'email' => $payload['email'],
            'password' => Hash::make($payload['password']),
            'country' => $countries->detect($request),
        ]);

        Auth::login($user);
        $request->session()->regenerate();
        $user->sendEmailVerificationNotification();

        return $this->me($request);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'data' => [
                'authenticated' => false,
                'csrfToken' => csrf_token(),
            ],
        ]);
    }
}

function initialsFromName(string $name): string
{
    return collect(explode(' ', trim($name)))
        ->filter()
        ->map(fn (string $part) => mb_substr($part, 0, 1))
        ->take(2)
        ->implode('');
}
