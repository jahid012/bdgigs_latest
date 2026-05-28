<?php

namespace App\Http\Controllers\Auth;

use App\Events\EmailVerified;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class EmailVerificationController extends Controller
{
    public function verify(Request $request, string $id, string $hash): RedirectResponse
    {
        $user = User::find($id);

        if (! $user || ! URL::hasValidSignature($request) || ! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return redirect('/verify-email/invalid');
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            $user->forceFill(['verification_status' => 'verified'])->save();

            event(new EmailVerified($user->fresh()));
        }

        return redirect('/verify-email/success?email='.urlencode((string) $user->email));
    }

    public function resend(Request $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json([
                'data' => [
                    'resent' => false,
                    'verified' => true,
                    'message' => 'Your email is already verified.',
                ],
            ]);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json([
            'data' => [
                'resent' => true,
                'verified' => false,
                'message' => 'Verification email sent.',
            ],
        ]);
    }
}
