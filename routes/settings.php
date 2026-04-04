<?php

use Laravel\Fortify\Features;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\Security;
use App\Livewire\Settings\Appearance;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('profile.edit');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('settings/appearance', Appearance::class)->name('appearance.edit');

    Route::get('settings/security', Security::class)
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('security.edit');
});
