<?php

namespace App\Http\Controllers;

use App\Services\EmailPreferenceTokenService;
use Illuminate\Http\Request;

class EmailPreferenceController extends Controller
{
    public function showPreferences(string $token, EmailPreferenceTokenService $tokens)
    {
        $record = $tokens->findValid($token);

        return view('email-preferences.show', [
            'token' => $token,
            'record' => $record,
            'user' => $record?->user,
        ]);
    }

    public function updatePreferences(Request $request, string $token, EmailPreferenceTokenService $tokens)
    {
        $payload = $request->validate([
            'email_type' => ['required', 'string', 'max:120'],
            'enabled' => ['nullable', 'boolean'],
        ]);

        $user = $tokens->updatePreference($token, $payload['email_type'], (bool) ($payload['enabled'] ?? false));

        return view('email-preferences.result', [
            'success' => (bool) $user,
            'title' => $user ? 'Email preferences updated' : 'Preference link expired',
            'message' => $user
                ? 'Your email preference was updated. Transactional and security emails will still be sent.'
                : 'This preferences link is invalid or expired.',
        ]);
    }

    public function showUnsubscribe(string $token, EmailPreferenceTokenService $tokens)
    {
        return view('email-preferences.unsubscribe', [
            'token' => $token,
            'record' => $tokens->findValid($token),
        ]);
    }

    public function confirmUnsubscribe(string $token, EmailPreferenceTokenService $tokens)
    {
        $user = $tokens->unsubscribeAllMarketing($token);

        return view('email-preferences.result', [
            'success' => (bool) $user,
            'title' => $user ? 'You are unsubscribed' : 'Unsubscribe link expired',
            'message' => $user
                ? 'Marketing emails are now disabled. Transactional and security emails will still be sent.'
                : 'This unsubscribe link is invalid or expired.',
        ]);
    }
}
