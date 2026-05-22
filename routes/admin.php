<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DisputeController;
use App\Http\Controllers\Admin\GigController;
use App\Http\Controllers\Admin\ManualPaymentController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
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

            Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

            Route::get('/users', [UserController::class, 'index'])->middleware('permission:users.view')->name('users');
            Route::post('/users/{user}/verify', [UserController::class, 'verify'])->middleware('permission:users.verify')->name('users.verify');
            Route::post('/users/{user}/suspend', [UserController::class, 'suspend'])->middleware('permission:users.suspend')->name('users.suspend');
            Route::post('/users/{user}/restore', [UserController::class, 'restore'])->middleware('permission:users.suspend')->name('users.restore');
            Route::post('/users/{user}/roles', [RoleController::class, 'updateUserRoles'])->middleware('permission:roles.manage')->name('users.roles.update');

            Route::get('/gigs', [GigController::class, 'index'])->middleware('permission:gigs.view')->name('gigs');
            Route::patch('/gigs/{gig}/status', [GigController::class, 'updateStatus'])->middleware('permission:gigs.review|gigs.publish')->name('gigs.status');
            Route::patch('/gigs/{gig}/featured', [GigController::class, 'toggleFeatured'])->middleware('permission:gigs.publish')->name('gigs.featured');

            Route::get('/orders', [OrderController::class, 'index'])->middleware('permission:orders.view')->name('orders');
            Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])->middleware('permission:orders.manage')->name('orders.status');

            Route::get('/payments', [PaymentController::class, 'index'])->middleware('permission:payments.view')->name('payments');
            Route::get('/manual-payments', [ManualPaymentController::class, 'index'])->middleware('permission:manual-payments.view')->name('manual-payments');
            Route::patch('/manual-payments/{submission}/review', [ManualPaymentController::class, 'review'])->middleware('permission:manual-payments.approve')->name('manual-payments.review');
            Route::get('/disputes', [DisputeController::class, 'index'])->middleware('permission:disputes.view')->name('disputes');
            Route::get('/reports', [ReportController::class, 'index'])->middleware('permission:reports.view')->name('reports');

            Route::get('/settings', [SettingController::class, 'edit'])->middleware('permission:settings.view')->name('settings');
            Route::post('/settings', [SettingController::class, 'update'])->middleware('permission:settings.update')->name('settings.update');

            Route::get('/roles', [RoleController::class, 'index'])->middleware('permission:roles.manage')->name('roles');
            Route::post('/roles', [RoleController::class, 'store'])->middleware('permission:roles.manage')->name('roles.store');
            Route::get('/roles/users', [RoleController::class, 'users'])->middleware('permission:roles.manage')->name('roles.users');
            Route::get('/roles/{role}/permissions', [RoleController::class, 'permissions'])->middleware('permission:roles.manage')->name('roles.permissions');
            Route::post('/roles/{role}/permissions', [RoleController::class, 'updatePermissions'])->middleware('permission:roles.manage')->name('roles.permissions.update');
        });

        Route::get('/{any}', function () {
            abort(404);
        })->where('any', '.*')->name('fallback');
    });
