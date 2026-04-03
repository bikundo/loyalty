<?php

use App\Actions\Fortify\CreateNewUser;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Enums\Role;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('a new merchant can register and a tenant is created', function () {
    // 1. Seed foundation data
    $this->seed(RolesAndPermissionsSeeder::class);
    Plan::factory()->create(['slug' => 'starter']);

    $action = new CreateNewUser();

    $input = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'business_name' => 'Johns Coffee',
    ];

    /** @var User $user */
    $user = $action->create($input);

    // 2. Verify User
    expect($user)->toBeInstanceOf(User::class);
    expect($user->name)->toBe('John Doe');
    expect($user->tenant_id)->not->toBeNull();
    expect($user->hasRole(Role::MerchantOwner->value))->toBeTrue();

    // 3. Verify Tenant
    $tenant = $user->tenant;
    expect($tenant)->toBeInstanceOf(Tenant::class);
    expect($tenant->name)->toBe('Johns Coffee');
    expect($tenant->owner_user_id)->toBe($user->id);
    expect($tenant->slug)->toBe('johns-coffee');

    // 4. Verify Defaults
    expect($tenant->settings)->not->toBeNull();
    expect($tenant->settings->programme_name)->toBe('Johns Coffee Rewards');
    expect($tenant->loyaltyProgram)->not->toBeNull();
    expect($tenant->smsWallet)->not->toBeNull();
    expect($tenant->smsWallet->credits_balance)->toBe(0);
});

test('registration requires a business name', function () {
    $action = new CreateNewUser();

    $input = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
    ];

    $action->create($input);
})->throws(\Illuminate\Validation\ValidationException::class);
