<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\DeactivateAccountRequest;
use App\Http\Requests\Api\SubmitIdentityVerificationRequest;
use App\Http\Requests\Api\UpdateAccountPasswordRequest;
use App\Http\Requests\Api\UpdateNotificationPreferencesRequest;
use App\Http\Resources\ActionResource;
use App\Http\Resources\IdentityVerificationResource;
use App\Http\Resources\NotificationPreferencesResource;
use App\Http\Resources\UserSettingsResource;
use App\Events\IdentityDocumentUploadFailed;
use App\Events\IdentityVerificationSubmitted;
use App\Events\PasswordChanged;
use App\Services\AccountStatusService;
use App\Services\NotificationPreferenceService;
use App\Services\UserSettingsSnapshotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSettingsController extends Controller
{
    public function show(Request $request, UserSettingsSnapshotService $settings): UserSettingsResource
    {
        return UserSettingsResource::make($settings->snapshot($request));
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

        return NotificationPreferencesResource::make($preferences)
            ->response()
            ->setStatusCode(200);
    }

    public function password(UpdateAccountPasswordRequest $request): ActionResource
    {
        $payload = $request->validated();

        abort_unless(Hash::check($payload['currentPassword'], $request->user()->password), 422, 'The current password is incorrect.');

        $request->user()->forceFill(['password' => Hash::make($payload['password'])])->save();
        event(new PasswordChanged($request->user()->fresh(), 'settings'));

        return ActionResource::make(['updated' => true]);
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

        return IdentityVerificationResource::make($submission)
            ->response()
            ->setStatusCode(201);
    }

    public function destroySession(Request $request, string $sessionId): ActionResource
    {
        DB::table(config('session.table', 'sessions'))
            ->where('user_id', $request->user()->id)
            ->where('id', '!=', $request->session()->getId())
            ->where('id', $sessionId)
            ->delete();

        return ActionResource::make(['revoked' => true]);
    }

    public function deactivate(DeactivateAccountRequest $request, AccountStatusService $accounts): ActionResource
    {
        $payload = $request->validated();

        abort_unless(Hash::check($payload['password'], $request->user()->password), 422, 'The current password is incorrect.');

        $user = $request->user();
        $accounts->deactivate($user, null, $payload['reason'] ?? 'User requested account deactivation.');

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return ActionResource::make(['deactivated' => true]);
    }
}
