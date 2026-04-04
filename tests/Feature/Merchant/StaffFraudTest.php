<?php

use App\Models\User;
use App\Models\Tenant;
use App\Models\Cashier;
use App\Models\Customer;
use App\Models\SmsWallet;
use App\Models\LoyaltyRule;
use App\Models\LoyaltyProgram;
use App\Services\Sms\SmsService;
use App\Services\AwardPointsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use App\Notifications\LowSmsBalanceNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('cashier cannot exceed daily award cap (KES)', function () {
    $tenant = Tenant::factory()->create();
    $program = LoyaltyProgram::factory()->for($tenant)->create(['is_active' => true]);

    // Spend-based rule: 1 point per 1 KES
    LoyaltyRule::factory()->for($program, 'programme')->create([
        'tenant_id' => $tenant->id,
        'type'      => 'spend',
        'config'    => [
            'points_awarded' => 1,
            'min_spend'      => 1,
            'multiplier'     => 1,
        ],
        'is_active' => true,
    ]);

    $user = User::factory()->for($tenant)->create();
    $cashier = Cashier::factory()->for($tenant)->create([
        'user_id'                 => $user->id,
        'name'                    => 'Test Cashier',
        'pin'                     => '1234',
        'daily_award_cap_kes'     => 1000,
        'total_awarded_today_kes' => 900,
    ]);

    $customer = Customer::factory()->for($tenant)->create();
    $service = app(AwardPointsService::class);

    // This should fail
    expect(fn() => $service->handle($tenant, $customer, ['amount_spent_kes' => 200], ['user_id' => $user->id]))
        ->toThrow(Exception::class, 'Daily award limit exceeded');

    // This should pass
    $service->handle($tenant, $customer, ['amount_spent_kes' => 50], ['user_id' => $user->id]);

    $cashier->refresh();
    expect($cashier->total_awarded_today_kes)->toBe(950);
});

test('low sms balance triggers notification only once per 24h', function () {
    Notification::fake();
    Cache::flush();

    $tenant = Tenant::factory()->create();
    $owner = User::factory()->for($tenant)->create();
    $tenant->update(['owner_user_id' => $owner->id]);

    $wallet = SmsWallet::factory()->for($tenant)->create([
        'credits_balance' => 40, // Below threshold of 50
    ]);

    $service = app(SmsService::class);

    // Mock successful provider response
    // We'll manually call the checkLowBalance to test the logic
    $method = new ReflectionMethod($service, 'checkLowBalance');
    $method->setAccessible(true);

    // First trigger
    $method->invoke($service, $tenant, $wallet);
    Notification::assertSentTo($owner, LowSmsBalanceNotification::class);

    // Second trigger (should be throttled)
    Notification::fake();
    $method->invoke($service, $tenant, $wallet);
    Notification::assertNotSentTo($owner, LowSmsBalanceNotification::class);
});
