<?php

use App\Http\Controllers\Api\Admin\PreviewPricingController;
use App\Http\Controllers\Api\Admin\PricingSlabController;
use App\Http\Controllers\Api\Promo\ValidatePromoController;
use App\Http\Controllers\Api\User\EsimCountryController;
use App\Http\Controllers\Api\User\EsimPackageController;
use App\Http\Controllers\Api\User\LoginController;
use App\Http\Controllers\Api\User\LogoutController;
use App\Http\Controllers\Api\User\NotificationController;
use App\Http\Controllers\Api\User\ProfileController;
use App\Http\Controllers\Api\User\RegisterController;
use App\Http\Controllers\Api\User\ShowEsimOrderController;
use App\Http\Controllers\Api\User\ShowWalletController;
use App\Http\Controllers\Api\User\StoreEsimOrderController;
use App\Http\Controllers\Api\User\StoreWalletController;
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
        Route::get('wallet', ShowWalletController::class)->name('api.user.wallet.show');
        Route::post('wallet', StoreWalletController::class)
            ->middleware('throttle:30,1')
            ->name('api.user.wallet.store');
        Route::get('notifications', [NotificationController::class, 'index'])->name('api.user.notifications.index');
        Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('api.user.notifications.read-all');
        Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('api.user.notifications.read');
        Route::get('esim/countries', EsimCountryController::class)->name('api.user.esim.countries');
        Route::get('esim/packages', EsimPackageController::class)->name('api.user.esim.packages');
        Route::post('esim/orders', StoreEsimOrderController::class)->name('api.user.esim.orders.store');
        Route::get('esim/orders/{order}', ShowEsimOrderController::class)->name('api.user.esim.orders.show');
    });
});

Route::prefix('admin')->middleware('admin.auth')->group(function () {
    Route::get('pricing/slabs', [PricingSlabController::class, 'index'])->name('api.admin.pricing.slabs.index');
    Route::post('pricing/slabs', [PricingSlabController::class, 'store'])->name('api.admin.pricing.slabs.store');
    Route::put('pricing/slabs/{slab}', [PricingSlabController::class, 'update'])->name('api.admin.pricing.slabs.update');
    Route::delete('pricing/slabs/{slab}', [PricingSlabController::class, 'destroy'])->name('api.admin.pricing.slabs.destroy');
    Route::post('pricing/preview', PreviewPricingController::class)->name('api.admin.pricing.preview');
});
