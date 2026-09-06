<?php

use App\Http\Controllers\Api\User\LoginController;
use App\Http\Controllers\Api\User\LogoutController;
use App\Http\Controllers\Api\User\ProfileController;
use App\Http\Controllers\Api\User\RegisterController;
use Illuminate\Support\Facades\Route;

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
    });
});
