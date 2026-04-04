<?php

use App\Models\Tenant;
use App\Models\Customer;
use App\Services\AnalyticsService;
use App\Models\PointTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('merchant dashboard stats are strictly tenant scoped', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();

    // Tenant 1 data
    Customer::factory()->for($tenant1)->count(5)->create(['created_at' => now()]);
    PointTransaction::factory()->for($tenant1)->create([
        'type' => 'earn',
        'points' => 100,
        'created_at' => now(),
    ]);

    // Tenant 2 data (should not be counted for Tenant 1)
    Customer::factory()->for($tenant2)->count(3)->create(['created_at' => now()]);
    PointTransaction::factory()->for($tenant2)->create([
        'type' => 'earn',
        'points' => 500,
        'created_at' => now(),
    ]);

    $service = new AnalyticsService();
    $stats = $service->getDashboardStats($tenant1);

    expect($stats['enrolments'])->toBe(5)
        ->and($stats['points_earned'])->toBe(100);
});

test('enrollment trend includes empty dates', function () {
    $tenant = Tenant::factory()->create();
    
    // Create customer 2 days ago
    Customer::factory()->for($tenant)->create(['created_at' => now()->subDays(2)]);

    $service = new AnalyticsService();
    $trend = $service->getEnrollmentTrend($tenant, 7);

    expect($trend)->toHaveCount(7)
        ->and($trend[now()->subDays(2)->format('Y-m-d')])->toBe(1)
        ->and($trend[now()->format('Y-m-d')])->toBe(0);
});
