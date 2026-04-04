<?php

use App\Models\Customer;
use App\Models\Reward;
use App\Models\Tenant;
use App\Models\PointTransaction;
use App\Models\Redemption;
use App\Services\RedemptionService;
use App\Services\Sms\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

uses(RefreshDatabase::class);

test('it can redeem a reward successfully', function () {
    $tenant = Tenant::factory()->create();
    $customer = Customer::factory()->for($tenant)->create([
        'total_points' => 1000
    ]);
    $reward = Reward::factory()->for($tenant)->create([
        'points_required' => 500,
        'redemptions_count' => 0
    ]);

    $smsService = Mockery::mock(SmsService::class);
    $smsService->shouldReceive('sendToCustomer')->andReturn(null);

    $service = new RedemptionService($smsService);
    $redemption = $service->redeem($customer, $reward, auth()->id());

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
        ->where('type', 'redemption')
        ->first();

    expect($transaction)->not->toBeNull()
        ->and($transaction->points)->toBe(-500)
        ->and($transaction->balance_after)->toBe(500);
});

test('it throws exception if customer has insufficient points', function () {
    $tenant = Tenant::factory()->create();
    $customer = Customer::factory()->for($tenant)->create([
        'total_points' => 100
    ]);
    $reward = Reward::factory()->for($tenant)->create([
        'points_required' => 500
    ]);

    $smsService = Mockery::mock(SmsService::class);
    $service = new RedemptionService($smsService);
    
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Insufficient points');

    $service->redeem($customer, $reward);
});

test('it throws exception if reward belongs to different tenant', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();
    
    $customer = Customer::factory()->for($tenant1)->create(['total_points' => 1000]);
    $reward = Reward::factory()->for($tenant2)->create(['points_required' => 500]);

    $smsService = Mockery::mock(SmsService::class);
    $service = new RedemptionService($smsService);

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Unauthorized reward selection for this customer');

    $service->redeem($customer, $reward);
});
