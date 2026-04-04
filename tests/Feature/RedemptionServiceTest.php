<?php

namespace Tests\Feature;

use Mockery;
use Exception;
use App\Models\User;
use App\Models\Reward;
use App\Models\Tenant;
use App\Models\Customer;
use App\Models\Redemption;
use App\Models\PointTransaction;
use App\Services\Sms\SmsService;
use App\Services\RedemptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it can redeem a reward successfully', function () {
    $tenant = Tenant::factory()->create();
    $customer = Customer::factory()->for($tenant)->create([
        'total_points' => 1000,
    ]);
    $reward = Reward::factory()->for($tenant)->create([
        'points_required'   => 500,
        'redemptions_count' => 0,
    ]);

    // Create a real user to satisfy the foreign key constraint
    $user = User::factory()->create();

    $smsService = Mockery::mock(SmsService::class);
    $smsService->shouldReceive('sendToCustomer')->once()->andReturn(null);

    $service = new RedemptionService($smsService);
    $redemption = $service->handle($tenant, $customer, $reward, ['user_id' => $user->id]);

    expect($redemption)->toBeInstanceOf(Redemption::class)
        ->and($redemption->status)->toBe('confirmed')
        ->and($redemption->points_used)->toBe(500);

    // Verify points were deducted
    $customer->refresh();
    expect($customer->total_points)->toBe(500);

    // Verify Reward counter incremented
    $reward->refresh();
    expect($reward->redemptions_count)->toBe(1);

    // Verify Ledger entry
    $transaction = PointTransaction::where('customer_id', $customer->id)
        ->where('type', 'redeem')
        ->first();

    expect($transaction)->not->toBeNull()
        ->and($transaction->points)->toBe(500)
        ->and($transaction->balance_after)->toBe(500)
        ->and($transaction->triggered_by_user_id)->toBe($user->id);
});

test('it throws exception if customer has insufficient points', function () {
    $tenant = Tenant::factory()->create();
    $customer = Customer::factory()->for($tenant)->create([
        'total_points' => 100,
    ]);
    $reward = Reward::factory()->for($tenant)->create([
        'points_required' => 500,
    ]);

    $smsService = Mockery::mock(SmsService::class);
    $service = new RedemptionService($smsService);

    expect(fn() => $service->handle($tenant, $customer, $reward))
        ->toThrow(Exception::class, 'Insufficient points');
});
