<?php

use App\Http\Responses\LoginResponse;
use Illuminate\Support\Facades\Route;
use App\Livewire\Merchant\Campaigns\CampaignIndex;
use App\Livewire\Merchant\Customers\CustomerTable;
use App\Livewire\Merchant\Rewards\RewardManagement;
use App\Livewire\Merchant\Settings\LoyaltySettings;
use App\Livewire\Merchant\Settings\StaffManagement;
use App\Livewire\Merchant\Customers\CustomerProfile;
use App\Livewire\Cashier\Dashboard as CashierDashboard;
use App\Livewire\Merchant\Analytics\AnalyticsDashboard;
use App\Livewire\Merchant\Dashboard as MerchantDashboard;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/dashboard', function () {
    return app(LoginResponse::class)->toResponse(request());
})->middleware(['auth'])->name('dashboard');

// 1. Merchant Dashboard Routes
Route::middleware(['auth', 'tenant'])->prefix('admin')->group(function () {
    Route::get('/dashboard', MerchantDashboard::class)->name('admin.dashboard');

    Route::get('/customers', CustomerTable::class)->name('admin.customers');
    Route::get('/customers/{customer}', CustomerProfile::class)->name('admin.customers.show');
    Route::get('/rewards', RewardManagement::class)->name('admin.rewards');
    Route::get('/campaigns', CampaignIndex::class)->name('admin.campaigns');
    Route::get('/analytics', AnalyticsDashboard::class)->name('admin.analytics');
    Route::get('/settings', StaffManagement::class)->name('admin.settings');
    Route::get('/settings/loyalty', LoyaltySettings::class)->name('admin.settings.loyalty');
});

// 2. Cashier Scanner Routes
Route::middleware(['auth', 'tenant'])->prefix('cashier')->group(function () {
    Route::get('/dashboard', CashierDashboard::class)->name('cashier.dashboard');
});

// 3. Customer Portal Routes
Route::middleware(['auth:customer', 'tenant'])->prefix('customer')->group(function () {
    Route::get('/dashboard', function () { return 'Customer Portal Coming Soon'; })->name('customer.dashboard');
});
