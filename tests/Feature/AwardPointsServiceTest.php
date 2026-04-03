<?php

use App\Models\Tenant;
use App\Models\Customer;
use App\Models\LoyaltyRule;
use App\Models\LoyaltyProgram;
use App\Services\PointsEngine;
use App\Services\AwardPointsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a point transaction and updates the customer ledger', function () {
    $tenant = Tenant::factory()->create();
    $program = LoyaltyProgram::factory()->create(['tenant_id' => $tenant->id]);
    $customer = Customer::factory()->create([
        'tenant_id'              => $tenant->id,
        'total_points'           => 10,
        'lifetime_points_earned' => 10,
        'total_visits'           => 0,
        'lifetime_spend_kes'     => 0,
    ]);

    LoyaltyRule::create([
        'tenant_id'          => $tenant->id,
        'loyalty_program_id' => $program->id,
        'name'               => '100 Point Visit',
        'type'               => 'visit',
        'is_active'          => true,
        'config'             => ['points_awarded' => 100],
        'priority'           => 1,
    ]);

    $engine = new PointsEngine();
    $service = new AwardPointsService($engine);

    $created = $service->handle($tenant, $customer, []);

    expect($created)->toHaveCount(1)
        ->and($created[0]->points)->toBe(100)
        ->and($created[0]->balance_after)->toBe(110);

    $customer->refresh();

    expect($customer->total_points)->toBe(110)
        ->and($customer->lifetime_points_earned)->toBe(110)
        ->and($customer->total_visits)->toBe(1);
});

it('returns empty array when no rules match', function () {
    $tenant = Tenant::factory()->create();
    $customer = Customer::factory()->create([
        'tenant_id'    => $tenant->id,
        'total_points' => 0,
    ]);

    $engine = new PointsEngine();
    $service = new AwardPointsService($engine);

    $created = $service->handle($tenant, $customer, ['amount_spent_kes' => 50]);

    expect($created)->toBeEmpty();

    $customer->refresh();
    expect($customer->total_points)->toBe(0);
});
