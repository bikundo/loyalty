<?php

use App\Models\Tenant;
use App\Models\Customer;
use App\Models\LoyaltyRule;
use App\Models\LoyaltyProgram;
use App\Services\PointsEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('awards correct points for a visit rule', function () {
    $tenant = Tenant::factory()->create();
    $program = LoyaltyProgram::factory()->create(['tenant_id' => $tenant->id]);
    $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);

    LoyaltyRule::create([
        'tenant_id'          => $tenant->id,
        'loyalty_program_id' => $program->id,
        'name'               => 'Store Visit',
        'type'               => 'visit',
        'is_active'          => true,
        'config'             => ['points_awarded' => 15],
        'priority'           => 1,
    ]);

    $engine = new PointsEngine();
    $awards = $engine->evaluate($tenant, $customer, []);

    expect($awards)->toHaveCount(1)
        ->and($awards[0]['points'])->toBe(15);
});

it('awards correct points for a spend rule', function () {
    $tenant = Tenant::factory()->create();
    $program = LoyaltyProgram::factory()->create(['tenant_id' => $tenant->id]);
    $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);

    LoyaltyRule::create([
        'tenant_id'          => $tenant->id,
        'loyalty_program_id' => $program->id,
        'name'               => '1pt per 10KES',
        'type'               => 'spend',
        'is_active'          => true,
        'config'             => [
            'min_spend_kes'  => 100,
            'points_per_kes' => 0.1,
        ],
        'priority' => 1,
    ]);

    $engine = new PointsEngine();

    // Below min spend — no points
    $awards = $engine->evaluate($tenant, $customer, ['amount_spent_kes' => 50]);
    expect($awards)->toHaveCount(0);

    // Above min spend — expect 20 points (200 * 0.1)
    $awards = $engine->evaluate($tenant, $customer, ['amount_spent_kes' => 200]);
    expect($awards)->toHaveCount(1)
        ->and($awards[0]['points'])->toBe(20);
});

it('respects stack_with_others constraint', function () {
    $tenant = Tenant::factory()->create();
    $program = LoyaltyProgram::factory()->create(['tenant_id' => $tenant->id]);
    $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);

    // Non-stackable rule (priority 1)
    LoyaltyRule::create([
        'tenant_id'          => $tenant->id,
        'loyalty_program_id' => $program->id,
        'name'               => 'Exclusive Visit',
        'type'               => 'visit',
        'is_active'          => true,
        'config'             => ['points_awarded' => 50],
        'priority'           => 1,
        'stack_with_others'  => false,
    ]);

    // Second rule (priority 2) — should be skipped because the first is non-stackable
    LoyaltyRule::create([
        'tenant_id'          => $tenant->id,
        'loyalty_program_id' => $program->id,
        'name'               => 'Bonus Spend',
        'type'               => 'visit',
        'is_active'          => true,
        'config'             => ['points_awarded' => 10],
        'priority'           => 2,
        'stack_with_others'  => true,
    ]);

    $engine = new PointsEngine();
    $awards = $engine->evaluate($tenant, $customer, []);

    expect($awards)->toHaveCount(1)
        ->and($awards[0]['points'])->toBe(50);
});

it('skips inactive rules', function () {
    $tenant = Tenant::factory()->create();
    $program = LoyaltyProgram::factory()->create(['tenant_id' => $tenant->id]);
    $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);

    LoyaltyRule::create([
        'tenant_id'          => $tenant->id,
        'loyalty_program_id' => $program->id,
        'name'               => 'Disabled Rule',
        'type'               => 'visit',
        'is_active'          => false,
        'config'             => ['points_awarded' => 100],
        'priority'           => 1,
    ]);

    $engine = new PointsEngine();
    $awards = $engine->evaluate($tenant, $customer, []);

    expect($awards)->toHaveCount(0);
});
