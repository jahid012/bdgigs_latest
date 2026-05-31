<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Resources\AuthSessionResource;
use App\Http\Resources\TwoFactorChallengeResource;
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
    public function me(Request $request): AuthSessionResource
    {
        return AuthSessionResource::make($request->user());
    }

    public function login(LoginRequest $request, SuspiciousActivityService $suspicious): AuthSessionResource|JsonResponse
    {
        $credentials = $request->validated();
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
            return TwoFactorChallengeResource::make([
                'message' => 'Use the two factor login challenge to continue.',
            ])
                ->response()
                ->setStatusCode(409);
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

        return AuthSessionResource::make($request->user());
    }

    public function register(
        RegisterRequest $request,
        CountryDetectorService $countries
    ): JsonResponse
    {
        $payload = $request->validated();

        $user = User::create([
            'name' => $payload['name'],
            'email' => $payload['email'],
            'password' => Hash::make($payload['password']),
            'country' => $countries->detect($request),
        ]);

        Auth::login($user);
        $request->session()->regenerate();
        $user->sendEmailVerificationNotification();

        return AuthSessionResource::make($request->user())
            ->response()
            ->setStatusCode(200);
    }

    public function logout(Request $request): AuthSessionResource
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return AuthSessionResource::make(null);
    }
}
