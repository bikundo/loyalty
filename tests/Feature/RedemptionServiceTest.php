<?php

use App\Models\Customer;
use App\Models\Reward;
use App\Models\Tenant;
use App\Models\PointTransaction;
use App\Models\Redemption;
use App\Services\RedemptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

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

    $service = new RedemptionService();
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
        ->where('type', 'redeem')
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

    $service = new RedemptionService();
    
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Insufficient points');

    $service->redeem($customer, $reward);
});
