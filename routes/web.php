<?php

use App\Http\Controllers\Admin\AdminPanelController;
use App\Http\Controllers\Admin\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::redirect('/', '/admin/dashboard')->name('home');

    Route::get('/dashboard', [AdminPanelController::class, 'dashboard'])->name('dashboard');
    Route::get('/users', [AdminPanelController::class, 'users'])->name('users');
    Route::get('/gigs', [AdminPanelController::class, 'gigs'])->name('gigs');
    Route::get('/orders', [AdminPanelController::class, 'orders'])->name('orders');
    Route::get('/payments', [AdminPanelController::class, 'payments'])->name('payments');
    Route::get('/disputes', [AdminPanelController::class, 'disputes'])->name('disputes');
    Route::get('/reports', [AdminPanelController::class, 'reports'])->name('reports');
    Route::get('/settings', [AdminPanelController::class, 'settings'])->name('settings');
});

Route::get('/admin/{any}', function () {
    abort(404);
})->where('any', '.*');

Route::view('/', 'app');
Route::view('/{any}', 'app')->where('any', '.*');
