<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\GigController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PresenceController;
use App\Http\Controllers\Api\PushSubscriptionController;
use App\Http\Controllers\Api\SavedServiceController;
use App\Http\Controllers\Api\SellerServiceController;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->name('api.')->group(function () {
    Route::get('/me', [AuthController::class, 'me'])->name('me');
    Route::post('/auth/login', [AuthController::class, 'login'])->name('auth.login');
    Route::post('/auth/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth')->name('auth.logout');

    Route::middleware('auth')->group(function () {
        Route::get('/gigs', [GigController::class, 'index'])->name('gigs.index');
        Route::get('/gigs/{gig:slug}', [GigController::class, 'show'])->name('gigs.show');

        Route::get('/seller/services', [SellerServiceController::class, 'index'])->name('seller.services.index');
        Route::post('/seller/services', [SellerServiceController::class, 'store'])->name('seller.services.store');
        Route::get('/seller/services/{gig:slug}', [SellerServiceController::class, 'show'])->name('seller.services.show');
        Route::patch('/seller/services/{gig:slug}', [SellerServiceController::class, 'update'])->name('seller.services.update');

        Route::get('/saved-services', [SavedServiceController::class, 'index'])->name('saved-services.index');
        Route::post('/saved-services/{gig:slug}', [SavedServiceController::class, 'store'])->name('saved-services.store');
        Route::delete('/saved-services/{gig:slug}', [SavedServiceController::class, 'destroy'])->name('saved-services.destroy');

        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');

        Route::get('/conversations', [ConversationController::class, 'index'])->name('conversations.index');
        Route::post('/conversations', [ConversationController::class, 'store'])->name('conversations.store');
        Route::get('/conversations/{conversation}', [ConversationController::class, 'show'])->name('conversations.show');
        Route::get('/conversations/{conversation}/messages', [ConversationController::class, 'messages'])->name('conversations.messages.index');
        Route::post('/conversations/{conversation}/messages', [ConversationController::class, 'storeMessage'])->name('conversations.messages.store');
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
