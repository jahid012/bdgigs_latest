<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CreatorMarketplaceItemController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DisputeController;
use App\Http\Controllers\Admin\EmailLogController;
use App\Http\Controllers\Admin\EmailTemplateController;
use App\Http\Controllers\Admin\GigController;
use App\Http\Controllers\Admin\ManualPaymentController;
use App\Http\Controllers\Admin\MarketplaceCategoryController;
use App\Http\Controllers\Admin\ModerationReportController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SellerApplicationController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SuspiciousActivityController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WithdrawalController;
use Illuminate\Support\Facades\Route;

Route::prefix(config('admin.route_prefix', 'admin'))
    ->name('admin.')
    ->group(function () {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login'])->name('login.submit');

        Route::middleware('auth')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
            Route::post('/impersonation/stop', [UserController::class, 'stopImpersonating'])->name('impersonation.stop');
        });

        Route::middleware(['auth', 'permission:admin.access'])->group(function () {
            Route::redirect('/', '/'.trim(config('admin.route_prefix', 'admin'), '/').'/dashboard')->name('home');

            Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

            Route::get('/users', [UserController::class, 'index'])->middleware('permission:users.view')->name('users');
            Route::get('/users/{user}', [UserController::class, 'show'])->middleware('permission:users.view')->name('users.show');
            Route::post('/users/{user}/impersonate', [UserController::class, 'impersonate'])->middleware('permission:users.impersonate')->name('users.impersonate');
            Route::post('/users/{user}/verify', [UserController::class, 'verify'])->middleware('permission:users.verify')->name('users.verify');
            Route::post('/users/{user}/suspend', [UserController::class, 'suspend'])->middleware('permission:users.suspend')->name('users.suspend');
            Route::post('/users/{user}/restore', [UserController::class, 'restore'])->middleware('permission:users.suspend')->name('users.restore');
            Route::post('/users/{user}/deactivate', [UserController::class, 'deactivate'])->middleware('permission:users.suspend')->name('users.deactivate');
            Route::patch('/users/{user}/identity/{submission}', [UserController::class, 'reviewIdentity'])->middleware('permission:users.verify')->name('users.identity.review');
            Route::post('/users/{user}/roles', [RoleController::class, 'updateUserRoles'])->middleware('permission:roles.manage')->name('users.roles.update');

            Route::get('/seller-applications', [SellerApplicationController::class, 'index'])->middleware('permission:users.verify')->name('seller-applications');
            Route::get('/seller-applications/{user}', [SellerApplicationController::class, 'show'])->middleware('permission:users.verify')->name('seller-applications.show');
            Route::post('/seller-applications/{user}/approve', [SellerApplicationController::class, 'approve'])->middleware('permission:users.verify')->name('seller-applications.approve');
            Route::post('/seller-applications/{user}/reject', [SellerApplicationController::class, 'reject'])->middleware('permission:users.verify')->name('seller-applications.reject');

            Route::get('/gigs', [GigController::class, 'index'])->middleware('permission:gigs.view')->name('gigs');
            Route::get('/gigs/{gig}', [GigController::class, 'show'])->middleware('permission:gigs.view')->name('gigs.show');
            Route::patch('/gigs/{gig}/status', [GigController::class, 'updateStatus'])->middleware('permission:gigs.review|gigs.publish')->name('gigs.status');
            Route::patch('/gigs/{gig}/featured', [GigController::class, 'toggleFeatured'])->middleware('permission:gigs.publish')->name('gigs.featured');
            Route::get('/marketplace-categories', [MarketplaceCategoryController::class, 'index'])->middleware('permission:categories.manage')->name('marketplace-categories');
            Route::post('/marketplace-categories', [MarketplaceCategoryController::class, 'store'])->middleware('permission:categories.manage')->name('marketplace-categories.store');
            Route::patch('/marketplace-categories/{category}', [MarketplaceCategoryController::class, 'update'])->middleware('permission:categories.manage')->name('marketplace-categories.update');
            Route::delete('/marketplace-categories/{category}', [MarketplaceCategoryController::class, 'destroy'])->middleware('permission:categories.manage')->name('marketplace-categories.destroy');
            Route::get('/creator-marketplace', [CreatorMarketplaceItemController::class, 'index'])->middleware('permission:content.manage')->name('creator-marketplace');
            Route::post('/creator-marketplace', [CreatorMarketplaceItemController::class, 'store'])->middleware('permission:content.manage')->name('creator-marketplace.store');
            Route::patch('/creator-marketplace/{item}', [CreatorMarketplaceItemController::class, 'update'])->middleware('permission:content.manage')->name('creator-marketplace.update');
            Route::delete('/creator-marketplace/{item}', [CreatorMarketplaceItemController::class, 'destroy'])->middleware('permission:content.manage')->name('creator-marketplace.destroy');

            Route::get('/orders', [OrderController::class, 'index'])->middleware('permission:orders.view')->name('orders');
            Route::get('/orders/{order:code}', [OrderController::class, 'show'])->middleware('permission:orders.view')->name('orders.show');
            Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])->middleware('permission:orders.manage')->name('orders.status');
            Route::post('/orders/{order}/refund', [OrderController::class, 'refund'])->middleware('permission:payments.release|orders.manage')->name('orders.refund');
            Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->middleware('permission:orders.manage')->name('orders.cancel');
            Route::post('/orders/{order}/disputes', [DisputeController::class, 'store'])->middleware('permission:disputes.resolve')->name('orders.disputes.store');

            Route::get('/payments', [PaymentController::class, 'index'])->middleware('permission:payments.view')->name('payments');
            Route::get('/manual-payments', [ManualPaymentController::class, 'index'])->middleware('permission:manual-payments.view')->name('manual-payments');
            Route::patch('/manual-payments/{submission}/review', [ManualPaymentController::class, 'review'])->middleware('permission:manual-payments.approve')->name('manual-payments.review');
            Route::get('/withdrawals', [WithdrawalController::class, 'index'])->middleware('permission:withdrawals.view')->name('withdrawals');
            Route::patch('/withdrawals/{withdrawal}/review', [WithdrawalController::class, 'review'])->middleware('permission:withdrawals.review|withdrawals.pay')->name('withdrawals.review');
            Route::get('/disputes', [DisputeController::class, 'index'])->middleware('permission:disputes.view')->name('disputes');
            Route::get('/disputes/{dispute:case_code}', [DisputeController::class, 'show'])->middleware('permission:disputes.view')->name('disputes.show');
            Route::patch('/disputes/{dispute:case_code}', [DisputeController::class, 'update'])->middleware('permission:disputes.resolve')->name('disputes.update');
            Route::post('/disputes/{dispute:case_code}/join', [DisputeController::class, 'join'])->middleware('permission:disputes.resolve')->name('disputes.join');
            Route::post('/disputes/{dispute:case_code}/evidence-request', [DisputeController::class, 'requestEvidence'])->middleware('permission:disputes.resolve')->name('disputes.evidence-request');
            Route::post('/disputes/{dispute:case_code}/refund', [DisputeController::class, 'refund'])->middleware('permission:payments.release|disputes.resolve')->name('disputes.refund');
            Route::get('/reports', [ReportController::class, 'index'])->middleware('permission:reports.view')->name('reports');
            Route::get('/moderation-reports', [ModerationReportController::class, 'index'])->middleware('permission:reports.view')->name('moderation-reports');
            Route::get('/moderation-reports/{report:code}', [ModerationReportController::class, 'show'])->middleware('permission:reports.view')->name('moderation-reports.show');
            Route::patch('/moderation-reports/{report:code}', [ModerationReportController::class, 'update'])->middleware('permission:reports.manage')->name('moderation-reports.update');
            Route::get('/suspicious-activities', [SuspiciousActivityController::class, 'index'])->middleware('permission:security.view')->name('suspicious-activities');
            Route::get('/suspicious-activities/{activity}', [SuspiciousActivityController::class, 'show'])->middleware('permission:security.view')->name('suspicious-activities.show');
            Route::patch('/suspicious-activities/{activity}/review', [SuspiciousActivityController::class, 'review'])->middleware('permission:security.review')->name('suspicious-activities.review');
            Route::get('/email-templates', [EmailTemplateController::class, 'index'])->middleware('permission:emails.manage')->name('email-templates');
            Route::post('/email-templates', [EmailTemplateController::class, 'store'])->middleware('permission:emails.manage')->name('email-templates.store');
            Route::get('/email-templates/{emailTemplate}', [EmailTemplateController::class, 'show'])->middleware('permission:emails.manage')->name('email-templates.show');
            Route::patch('/email-templates/{emailTemplate}', [EmailTemplateController::class, 'update'])->middleware('permission:emails.manage')->name('email-templates.update');
            Route::post('/email-templates/{emailTemplate}/reset', [EmailTemplateController::class, 'reset'])->middleware('permission:emails.manage')->name('email-templates.reset');
            Route::get('/email-templates/{emailTemplate}/preview', [EmailTemplateController::class, 'preview'])->middleware('permission:emails.manage')->name('email-templates.preview');
            Route::post('/email-templates/{emailTemplate}/test', [EmailTemplateController::class, 'sendTest'])->middleware('permission:emails.manage')->name('email-templates.test');
            Route::get('/email-logs', [EmailLogController::class, 'index'])->middleware('permission:emails.manage')->name('email-logs');
            Route::get('/email-logs/{emailLog}', [EmailLogController::class, 'show'])->middleware('permission:emails.manage')->name('email-logs.show');
            Route::post('/email-logs/{emailLog}/retry', [EmailLogController::class, 'retry'])->middleware('permission:emails.manage')->name('email-logs.retry');

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
