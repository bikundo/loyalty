<?php

use Tests\TestCase;
use App\Models\Tenant;
use App\Models\Campaign;
use App\Models\Customer;
use App\Models\PointTransaction;
use App\Services\AnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->service = new AnalyticsService();
    $this->tenant = Tenant::factory()->create();
    $this->otherTenant = Tenant::factory()->create();
});

test('it calculates summary stats correctly and respects multi-tenancy', function () {
    // Current tenant data
    $customer = Customer::factory()->create([
        'tenant_id'     => $this->tenant->id,
        'total_points'  => 100,
        'last_visit_at' => now(),
    ]);

    PointTransaction::create([
        'tenant_id'        => $this->tenant->id,
        'customer_id'      => $customer->id,
        'type'             => 'earn',
        'points'           => 100,
        'balance_after'    => 100,
        'amount_spent_kes' => 1000,
    ]);

    // Other tenant data (should be ignored)
    $otherCustomer = Customer::factory()->create([
        'tenant_id'    => $this->otherTenant->id,
        'total_points' => 500,
    ]);

    $stats = $this->service->getSummaryStats($this->tenant);

    expect($stats['active_customers'])->toBe(1)
        ->and($stats['points_liability'])->toBe(100)
        ->and($stats['revenue_influenced'])->toBe(1000);
});

test('it formats enrollment trends correctly for flux charts', function () {
    Customer::factory()->create([
        'tenant_id'  => $this->tenant->id,
        'created_at' => now()->subDays(1),
    ]);

    $trend = $this->service->getEnrollmentTrend($this->tenant, 7);

    expect($trend)->toBeArray()
        ->and(count($trend))->toBe(7)
        ->and($trend[5]['value'])->toBe(1); // yesterday
});

test('it calculates campaign ROI correctly', function () {
    $campaign = Campaign::create([
        'tenant_id'        => $this->tenant->id,
        'name'             => 'Test Campaign',
        'message'          => 'Test Message',
        'recipients_total' => 2,
        'dispatched_at'    => now()->subDays(2),
        'status'           => 'completed',
    ]);

    $c1 = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
    $c2 = Customer::factory()->create(['tenant_id' => $this->tenant->id]);

    $campaign->recipients()->create(['customer_id' => $c1->id, 'status' => 'sent']);
    $campaign->recipients()->create(['customer_id' => $c2->id, 'status' => 'sent']);

    // Only c1 visits within 7 days
    PointTransaction::create([
        'tenant_id'     => $this->tenant->id,
        'customer_id'   => $c1->id,
        'type'          => 'earn',
        'points'        => 50,
        'balance_after' => 50,
        'created_at'    => now()->subDays(1),
    ]);

    $roi = $this->service->getCampaignROI($campaign);

    expect($roi['total_recipients'])->toBe(2)
        ->and($roi['converted_visitors'])->toBe(1)
        ->and($roi['conversion_rate'])->toBe(50.0);
});
