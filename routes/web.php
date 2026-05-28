<?php

use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\EmailPreferenceController;
use Illuminate\Support\Facades\Route;

Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->name('verification.verify');

Route::get('/email/preferences/{token}', [EmailPreferenceController::class, 'showPreferences'])
    ->name('email.preferences.public');
Route::post('/email/preferences/{token}', [EmailPreferenceController::class, 'updatePreferences'])
    ->name('email.preferences.update');
Route::get('/email/unsubscribe/{token}', [EmailPreferenceController::class, 'showUnsubscribe'])
    ->name('email.unsubscribe');
Route::post('/email/unsubscribe/{token}/confirm', [EmailPreferenceController::class, 'confirmUnsubscribe'])
    ->name('email.unsubscribe.confirm');

Route::view('/', 'app');
Route::view('/{any}', 'app')->where('any', '.*');
