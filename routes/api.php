<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BillingController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\GigController;
use App\Http\Controllers\Api\ManualCheckoutController;
use App\Http\Controllers\Api\MessageSaveController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PresenceController;
use App\Http\Controllers\Api\PushSubscriptionController;
use App\Http\Controllers\Api\SavedServiceController;
use App\Http\Controllers\Api\SellerServiceController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserSettingsController;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->name('api.')->group(function () {
    Route::get('/me', [AuthController::class, 'me'])->name('me');
    Route::post('/auth/login', [AuthController::class, 'login'])->name('auth.login');
    Route::post('/auth/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth')->name('auth.logout');
    Route::get('/gigs', [GigController::class, 'index'])->name('gigs.index');
    Route::get('/gigs/{gig:slug}', [GigController::class, 'show'])->name('gigs.show');
    Route::get('/users/{username}/profile', [UserController::class, 'publicSellerProfile'])->name('users.profile');

    Route::middleware(['auth', 'active'])->group(function () {
        Route::get('/user/dashboard', [UserController::class, 'dashboard'])->name('user.dashboard');
        Route::get('/user/profile/buyer', [UserController::class, 'buyerProfile'])->name('user.profile.buyer');
        Route::patch('/user/profile/buyer', [UserController::class, 'updateBuyerProfile'])->name('user.profile.buyer.update');
        Route::get('/user/profile/seller', [UserController::class, 'sellerProfile'])->name('user.profile.seller');
        Route::patch('/user/profile/seller', [UserController::class, 'updateSellerProfile'])->name('user.profile.seller.update');
        Route::post('/user/avatar', [UserController::class, 'avatar'])->name('user.avatar');

        Route::get('/billing/profile', [BillingController::class, 'show'])->name('billing.profile');
        Route::patch('/billing/profile', [BillingController::class, 'update'])->name('billing.profile.update');
        Route::get('/billing/summary', [BillingController::class, 'buyerSummary'])->name('billing.summary');
        Route::get('/seller/earnings', [BillingController::class, 'sellerEarnings'])->name('seller.earnings');

        Route::get('/user/settings', [UserSettingsController::class, 'show'])->name('user.settings');
        Route::patch('/user/settings/notifications', [UserSettingsController::class, 'notifications'])->name('user.settings.notifications');
        Route::patch('/user/settings/password', [UserSettingsController::class, 'password'])->name('user.settings.password');
        Route::post('/user/settings/identity-verification', [UserSettingsController::class, 'submitIdentity'])->name('user.settings.identity');
        Route::delete('/user/settings/sessions/{sessionId}', [UserSettingsController::class, 'destroySession'])->name('user.settings.sessions.destroy');
        Route::post('/user/settings/deactivate', [UserSettingsController::class, 'deactivate'])->name('user.settings.deactivate');

        Route::get('/seller/services', [SellerServiceController::class, 'index'])->name('seller.services.index');
        Route::post('/seller/services', [SellerServiceController::class, 'store'])->name('seller.services.store');
        Route::get('/seller/services/{gig:slug}', [SellerServiceController::class, 'show'])->name('seller.services.show');
        Route::patch('/seller/services/{gig:slug}', [SellerServiceController::class, 'update'])->name('seller.services.update');
        Route::patch('/seller/services/{gig:slug}/status', [SellerServiceController::class, 'updateStatus'])->name('seller.services.status');
        Route::delete('/seller/services/{gig:slug}', [SellerServiceController::class, 'destroy'])->name('seller.services.destroy');

        Route::get('/saved-services', [SavedServiceController::class, 'index'])->name('saved-services.index');
        Route::post('/saved-services/{gig:slug}', [SavedServiceController::class, 'store'])->name('saved-services.store');
        Route::delete('/saved-services/{gig:slug}', [SavedServiceController::class, 'destroy'])->name('saved-services.destroy');

        Route::get('/manual-payment-methods', [ManualCheckoutController::class, 'methods'])->name('manual-payment-methods.index');
        Route::post('/gigs/{gig:slug}/manual-checkout', [ManualCheckoutController::class, 'store'])->name('gigs.manual-checkout');

        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order:code}', [OrderController::class, 'show'])->name('orders.show');

        Route::get('/conversations', [ConversationController::class, 'index'])->name('conversations.index');
        Route::post('/conversations', [ConversationController::class, 'store'])->name('conversations.store');
        Route::get('/conversations/{conversation}', [ConversationController::class, 'show'])->name('conversations.show');
        Route::get('/conversations/{conversation}/messages', [ConversationController::class, 'messages'])->name('conversations.messages.index');
        Route::post('/conversations/{conversation}/messages', [ConversationController::class, 'storeMessage'])->name('conversations.messages.store');
        Route::get('/conversations/{conversation}/saved-messages', [MessageSaveController::class, 'index'])->name('conversations.saved-messages.index');
        Route::post('/messages/{message}/save', [MessageSaveController::class, 'store'])->name('messages.save');
        Route::delete('/messages/{message}/save', [MessageSaveController::class, 'destroy'])->name('messages.unsave');
        Route::patch('/conversations/{conversation}/read', [ConversationController::class, 'markRead'])->name('conversations.read');
        Route::post('/conversations/{conversation}/typing', [ConversationController::class, 'typing'])->name('conversations.typing');

        Route::post('/presence/heartbeat', [PresenceController::class, 'heartbeat'])->name('presence.heartbeat');
        Route::post('/push-subscriptions', [PushSubscriptionController::class, 'store'])->name('push-subscriptions.store');
        Route::delete('/push-subscriptions/{token}', [PushSubscriptionController::class, 'destroy'])->name('push-subscriptions.destroy');

        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
        Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    });
});
