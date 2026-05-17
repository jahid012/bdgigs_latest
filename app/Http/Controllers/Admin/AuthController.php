<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (session('admin_authenticated')) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $email = strtolower($credentials['email']);
        $adminEmail = strtolower(config('admin.email'));
        $adminPassword = config('admin.password');

        if (
            ! hash_equals($adminEmail, $email) ||
            ! hash_equals($adminPassword, $credentials['password'])
        ) {
            throw ValidationException::withMessages([
                'email' => 'These admin credentials do not match our records.',
            ]);
        }

        $request->session()->regenerate();
        $request->session()->put('admin_authenticated', true);
        $request->session()->put('admin_user', [
            'name' => config('admin.name'),
            'email' => config('admin.email'),
        ]);

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request)
    {
        $request->session()->forget(['admin_authenticated', 'admin_user']);
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
