<?php

use App\Http\Controllers\Api\Promo\ValidatePromoController;
use App\Http\Controllers\Api\User\EsimCountryController;
use App\Http\Controllers\Api\User\EsimPackageController;
use App\Http\Controllers\Api\User\IndexBannerController;
use App\Http\Controllers\Api\User\IndexEsimOrderController;
use App\Http\Controllers\Api\User\IndexUserTransactionsController;
use App\Http\Controllers\Api\User\LoginController;
use App\Http\Controllers\Api\User\LogoutController;
use App\Http\Controllers\Api\User\NotificationController;
use App\Http\Controllers\Api\User\ProfileController;
use App\Http\Controllers\Api\User\RegisterController;
use App\Http\Controllers\Api\User\ShowEsimOrderController;
use App\Http\Controllers\Api\User\ShowWalletController;
use App\Http\Controllers\Api\User\StoreEsimOrderController;
use App\Http\Controllers\Api\User\StoreWalletController;
use App\Http\Controllers\Api\User\UpdateProfileController;
use Illuminate\Support\Facades\Route;

Route::post('promo/validate', ValidatePromoController::class)
    ->middleware('throttle:30,1')
    ->name('api.promo.validate');

Route::prefix('user')->group(function () {
    Route::post('register', RegisterController::class)
        ->middleware('throttle:10,1')
        ->name('api.user.register');

    Route::post('login', LoginController::class)
        ->middleware('throttle:10,1')
        ->name('api.user.login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', LogoutController::class)->name('api.user.logout');
        Route::get('profile', ProfileController::class)->name('api.user.profile');
        Route::put('profile', UpdateProfileController::class)
            ->middleware('throttle:30,1')
            ->name('api.user.profile.update');
        Route::get('banners', IndexBannerController::class)->name('api.user.banners.index');
        Route::get('wallet', ShowWalletController::class)->name('api.user.wallet.show');
        Route::post('wallet', StoreWalletController::class)
            ->middleware('throttle:30,1')
            ->name('api.user.wallet.store');
        Route::get('transactions', IndexUserTransactionsController::class)
            ->name('api.user.transactions.index');
        Route::get('notifications', [NotificationController::class, 'index'])->name('api.user.notifications.index');
        Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('api.user.notifications.read-all');
        Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('api.user.notifications.read');
        Route::get('esim/countries', EsimCountryController::class)->name('api.user.esim.countries');
        Route::get('esim/packages', EsimPackageController::class)->name('api.user.esim.packages');
        Route::post('esim/orders', StoreEsimOrderController::class)->name('api.user.esim.orders.store');
        Route::get('esim/orders', IndexEsimOrderController::class)->name('api.user.esim.orders.index');
        Route::get('esim/orders/{order}', ShowEsimOrderController::class)->name('api.user.esim.orders.show');
    });
});
