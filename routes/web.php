<?php

use App\Http\Controllers\Auth\AdminLogoutController;
use App\Http\Controllers\Auth\PartnerLogoutController;
use App\Livewire\Auth\AdminLogin;
use App\Livewire\Auth\PartnerLogin;
use App\Livewire\Admin\Applications;
use App\Livewire\Admin\Partners;
use App\Livewire\Admin\Payouts;
use App\Livewire\Admin\Profile as AdminProfile;
use App\Livewire\Admin\PromoCodes;
use App\Livewire\Admin\Settings as AdminSettings;
use App\Livewire\Admin\Statistics as AdminStatistics;
use App\Livewire\Partner\Dashboard;
use App\Livewire\Partner\Earnings;
use App\Livewire\Partner\PromoCodes as PartnerPromoCodes;
use App\Livewire\Partner\Registrations;
use App\Livewire\Partner\Settings as PartnerSettings;
use App\Livewire\Partner\Statistics as PartnerStatistics;
use App\Livewire\Public\ApplyForm;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/apply');

Route::get('/locale/{locale}', \App\Http\Controllers\LocaleController::class)
    ->where('locale', '[a-z]{2}')
    ->name('locale.switch');

Route::get('/apply', ApplyForm::class)->name('apply');

Route::middleware('guest:partner')->group(function () {
    Route::get('/login', PartnerLogin::class)->name('login');
});

Route::middleware('guest:admin')->group(function () {
    Route::get('/admin/login', AdminLogin::class)->name('admin.login');
});

Route::prefix('partner')->name('partner.')->middleware('partner.auth')->group(function () {
    Route::get('/', Dashboard::class)->name('dashboard');
    Route::get('/registrations', Registrations::class)->name('registrations');
    Route::get('/earnings', Earnings::class)->name('earnings');
    Route::get('/promo-codes', PartnerPromoCodes::class)->name('promo-codes');
    Route::get('/statistics', PartnerStatistics::class)->name('statistics');
    Route::get('/settings', PartnerSettings::class)->name('settings');
    Route::post('/logout', PartnerLogoutController::class)->name('logout');
});

Route::prefix('admin')->name('admin.')->middleware('admin.auth')->group(function () {
    Route::redirect('/', '/admin/applications');
    Route::get('/applications', Applications::class)->name('applications');
    Route::get('/partners', Partners::class)->name('partners');
    Route::get('/promo-codes', PromoCodes::class)->name('promo-codes');
    Route::get('/payouts', Payouts::class)->name('payouts');
    Route::get('/statistics', AdminStatistics::class)->name('statistics');
    Route::get('/settings', AdminSettings::class)->name('settings');
    Route::get('/profile', AdminProfile::class)->name('profile');
    Route::post('/logout', AdminLogoutController::class)->name('logout');
});
