<?php

use App\Http\Controllers\Admin\AdminPanelController;
use App\Http\Controllers\Admin\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix(config('admin.route_prefix', 'admin'))
    ->name('admin.')
    ->group(function () {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login'])->name('login.submit');

        Route::middleware('auth')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        });

        Route::middleware(['auth', 'permission:admin.access'])->group(function () {
            Route::redirect('/', '/'.trim(config('admin.route_prefix', 'admin'), '/').'/dashboard')->name('home');

            Route::get('/dashboard', [AdminPanelController::class, 'dashboard'])->name('dashboard');
            Route::get('/users', [AdminPanelController::class, 'users'])->middleware('permission:users.view')->name('users');
            Route::get('/gigs', [AdminPanelController::class, 'gigs'])->middleware('permission:gigs.view')->name('gigs');
            Route::get('/orders', [AdminPanelController::class, 'orders'])->middleware('permission:orders.view')->name('orders');
            Route::get('/payments', [AdminPanelController::class, 'payments'])->middleware('permission:payments.view')->name('payments');
            Route::get('/disputes', [AdminPanelController::class, 'disputes'])->middleware('permission:disputes.view')->name('disputes');
            Route::get('/reports', [AdminPanelController::class, 'reports'])->middleware('permission:reports.view')->name('reports');
            Route::get('/settings', [AdminPanelController::class, 'settings'])->middleware('permission:settings.view')->name('settings');
            Route::post('/settings', [AdminPanelController::class, 'updateSettings'])->middleware('permission:settings.update')->name('settings.update');
            Route::get('/roles', [AdminPanelController::class, 'roles'])->middleware('permission:roles.manage')->name('roles');
            Route::post('/roles', [AdminPanelController::class, 'storeRole'])->middleware('permission:roles.manage')->name('roles.store');
            Route::get('/roles/users', [AdminPanelController::class, 'roleUsers'])->middleware('permission:roles.manage')->name('roles.users');
            Route::get('/roles/{role}/permissions', [AdminPanelController::class, 'rolePermissions'])->middleware('permission:roles.manage')->name('roles.permissions');
            Route::post('/roles/{role}/permissions', [AdminPanelController::class, 'updateRolePermissions'])->middleware('permission:roles.manage')->name('roles.permissions.update');
            Route::post('/users/{user}/roles', [AdminPanelController::class, 'updateUserRoles'])->middleware('permission:roles.manage')->name('users.roles.update');
        });

        Route::get('/{any}', function () {
            abort(404);
        })->where('any', '.*')->name('fallback');
    });
