<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\DeactivateAccountRequest;
use App\Http\Requests\Api\SubmitIdentityVerificationRequest;
use App\Http\Requests\Api\UpdateAccountPasswordRequest;
use App\Http\Requests\Api\UpdateNotificationPreferencesRequest;
use App\Events\IdentityDocumentUploadFailed;
use App\Events\IdentityVerificationSubmitted;
use App\Events\PasswordChanged;
use App\Services\AccountStatusService;
use App\Services\NotificationPreferenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSettingsController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $preferences = $user->notificationPreference()->firstOrCreate([]);
        $identity = $user->identityVerificationSubmissions()->latest()->first();

        return response()->json([
            'data' => [
                'account' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'username' => $user->username,
                    'country' => $user->country,
                    'visibility' => $user->last_seen_at?->greaterThan(now()->subSeconds(90)) ? 'Online' : 'Offline',
                    'verificationStatus' => $user->verification_status,
                    'emailVerified' => $user->hasVerifiedEmail(),
                    'emailVerifiedAt' => $user->email_verified_at?->toISOString(),
                    'accountStatus' => $user->suspended_at
                        ? 'suspended'
                        : ($user->deactivated_at ? 'deactivated' : 'active'),
                    'sellerStatus' => $user->seller_status ?: 'not_applied',
                    'marketingUnsubscribed' => (bool) $user->marketing_unsubscribed_at,
                    'twoFactorEnabled' => filled($user->two_factor_secret),
                ],
                'notifications' => [
                    'preferences' => $preferences->preferences ?: [],
                    'realtimeEnabled' => $preferences->realtime_enabled,
                    'soundEnabled' => $preferences->sound_enabled,
                ],
                'sessions' => $this->sessions($request),
                'identity' => $identity ? [
                    'status' => $identity->status,
                    'details' => $identity->details ?: [],
                    'documentPath' => $identity->document_path,
                    'submittedAt' => $identity->submitted_at?->toISOString(),
                    'reviewNote' => $identity->review_note,
                    'additionalDocumentNote' => $identity->additional_document_note,
                ] : null,
            ],
        ]);
    }

    public function notifications(
        UpdateNotificationPreferencesRequest $request,
        NotificationPreferenceService $notificationPreferences
    ): JsonResponse
    {
        $payload = $request->validated();

        $preferences = $request->user()->notificationPreference()->updateOrCreate([], [
            'preferences' => $payload['preferences'] ?? [],
            'realtime_enabled' => $payload['realtimeEnabled'],
            'sound_enabled' => $payload['soundEnabled'],
        ]);
        $notificationPreferences->syncEmailPreferencesFromDashboard(
            $request->user(),
            $preferences->preferences ?: [],
        );

        return response()->json([
            'data' => [
                'preferences' => $preferences->preferences ?: [],
                'realtimeEnabled' => $preferences->realtime_enabled,
                'soundEnabled' => $preferences->sound_enabled,
            ],
        ]);
    }

    public function password(UpdateAccountPasswordRequest $request): JsonResponse
    {
        $payload = $request->validated();

        abort_unless(Hash::check($payload['currentPassword'], $request->user()->password), 422, 'The current password is incorrect.');

        $request->user()->forceFill(['password' => Hash::make($payload['password'])])->save();
        event(new PasswordChanged($request->user()->fresh(), 'settings'));

        return response()->json(['data' => ['updated' => true]]);
    }

    public function submitIdentity(SubmitIdentityVerificationRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $file = $request->file('document');
        $directory = public_path("uploads/identity/{$request->user()->id}");

        try {
            File::ensureDirectoryExists($directory);

            $extension = $file->getClientOriginalExtension() ?: 'bin';
            $filename = Str::uuid()->toString().'.'.$extension;
            $file->move($directory, $filename);
            $documentPath = "/uploads/identity/{$request->user()->id}/{$filename}";
        } catch (\Throwable $exception) {
            event(new IdentityDocumentUploadFailed($request->user(), $exception->getMessage()));
            abort(422, 'The identity document could not be uploaded. Please try again.');
        }

        $submission = $request->user()->identityVerificationSubmissions()->create([
            'status' => 'submitted',
            'details' => collect($payload)->except('document')->all(),
            'document_path' => $documentPath,
            'submitted_at' => now(),
        ]);
        $request->user()->forceFill(['verification_status' => 'submitted'])->save();
        event(new IdentityVerificationSubmitted($submission->fresh(['user'])));

        return response()->json([
            'data' => [
                'status' => $submission->status,
                'details' => $submission->details,
                'documentPath' => $submission->document_path,
                'submittedAt' => $submission->submitted_at?->toISOString(),
                'reviewNote' => $submission->review_note,
                'additionalDocumentNote' => $submission->additional_document_note,
            ],
        ], 201);
    }

    public function destroySession(Request $request, string $sessionId): JsonResponse
    {
        DB::table(config('session.table', 'sessions'))
            ->where('user_id', $request->user()->id)
            ->where('id', '!=', $request->session()->getId())
            ->where('id', $sessionId)
            ->delete();

        return response()->json(['data' => ['revoked' => true]]);
    }

    public function deactivate(DeactivateAccountRequest $request, AccountStatusService $accounts): JsonResponse
    {
        $payload = $request->validated();

        abort_unless(Hash::check($payload['password'], $request->user()->password), 422, 'The current password is incorrect.');

        $user = $request->user();
        $accounts->deactivate($user, null, $payload['reason'] ?? 'User requested account deactivation.');

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['data' => ['deactivated' => true]]);
    }

    private function sessions(Request $request): array
    {
        return DB::table(config('session.table', 'sessions'))
            ->where('user_id', $request->user()->id)
            ->latest('last_activity')
            ->get(['id', 'ip_address', 'user_agent', 'last_activity'])
            ->map(fn ($session) => [
                'id' => $session->id,
                'ipAddress' => $session->ip_address,
                'userAgent' => $session->user_agent,
                'lastActivity' => now()->setTimestamp($session->last_activity)->diffForHumans(),
                'current' => $session->id === $request->session()->getId(),
            ])
            ->values()
            ->all();
    }
}
