<?php

use Mockery;
use App\Models\Tenant;
use App\Models\Customer;
use App\Models\LoyaltyRule;
use App\Models\LoyaltyProgram;
use App\Services\PointsEngine;
use App\Services\Sms\SmsService;
use App\Services\AwardPointsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it awards points based on spend amount', function () {
    $tenant = Tenant::factory()->create();
    $program = LoyaltyProgram::factory()->for($tenant)->create(['is_active' => true]);
    $customer = Customer::factory()->for($tenant)->create([
        'total_points'           => 0,
        'lifetime_points_earned' => 0,
        'lifetime_spend_kes'     => 0,
        'total_visits'           => 0,
    ]);

    // Create a rule: 1 point for every 10 KES spent
    $rule = LoyaltyRule::factory()->for($tenant)->create([
        'loyalty_program_id' => $program->id,
        'name'               => 'Spend 10 earn 1',
        'type'               => 'spend',
        'is_active'          => true,
        'config'             => [
            'min_spend_kes'  => 10,
            'points_per_kes' => 0.1,
        ],
    ]);

    $smsService = Mockery::mock(SmsService::class);
    $smsService->shouldReceive('sendToCustomer')->andReturn(null);

    $service = new AwardPointsService(new PointsEngine(), $smsService);

    $transactions = $service->handle(
        $tenant,
        $customer,
        ['amount_spent_kes' => 1000]
    );

    expect($transactions)->toHaveCount(1);
    expect($transactions[0]->points)->toBe(100);
    expect($transactions[0]->type)->toBe('earn');

    $customer->refresh();
    expect($customer->total_points)->toBe(100);
    expect($customer->lifetime_points_earned)->toBe(100);
    expect($customer->lifetime_spend_kes)->toBe(1000);
    expect($customer->total_visits)->toBe(1);
});

test('it awards points based on visit', function () {
    $tenant = Tenant::factory()->create();
    $program = LoyaltyProgram::factory()->for($tenant)->create(['is_active' => true]);
    $customer = Customer::factory()->for($tenant)->create(['total_points' => 0]);

    // Create a rule: 5 points per visit
    $rule = LoyaltyRule::factory()->for($tenant)->create([
        'loyalty_program_id' => $program->id,
        'name'               => 'Visit Bonus',
        'type'               => 'visit',
        'is_active'          => true,
        'config'             => [
            'points_awarded' => 5,
        ],
    ]);

    $smsService = Mockery::mock(SmsService::class);
    $smsService->shouldReceive('sendToCustomer')->andReturn(null);

    $service = new AwardPointsService(new PointsEngine(), $smsService);

    $transactions = $service->handle($tenant, $customer, []);

    expect($transactions)->toHaveCount(1);
    expect($transactions[0]->points)->toBe(5);

    $customer->refresh();
    expect($customer->total_points)->toBe(5);
    expect($customer->total_visits)->toBe(1);
});

test('it does not award points if minimum spend is not met', function () {
    $tenant = Tenant::factory()->create();
    $program = LoyaltyProgram::factory()->for($tenant)->create(['is_active' => true]);
    $customer = Customer::factory()->for($tenant)->create(['total_points' => 0]);

    // Create a rule: 1 point per 10 KES, min spend 100
    $rule = LoyaltyRule::factory()->for($tenant)->create([
        'loyalty_program_id' => $program->id,
        'type'               => 'spend',
        'is_active'          => true,
        'config'             => [
            'min_spend_kes'  => 100,
            'points_per_kes' => 0.1,
        ],
    ]);

    $smsService = Mockery::mock(SmsService::class);
    $smsService->shouldReceive('sendToCustomer')->andReturn(null);

    $service = new AwardPointsService(new PointsEngine(), $smsService);

    $transactions = $service->handle($tenant, $customer, ['amount_spent_kes' => 50]);

    expect($transactions)->toBeEmpty();

    $customer->refresh();
    expect($customer->total_points)->toBe(0);
});
