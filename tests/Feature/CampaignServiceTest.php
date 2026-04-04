<?php

use App\Models\User;
use App\Models\Tenant;
use App\Models\Campaign;
use App\Models\Customer;
use App\Models\SmsWallet;
use App\Services\CampaignService;
use App\Services\Sms\SmsProviderInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->wallet = SmsWallet::factory()->create([
        'tenant_id'        => $this->tenant->id,
        'credits_balance'  => 1000,
        'credits_reserved' => 0,
    ]);
    $this->user = User::factory()->create();

    // Mock SMS Provider to return success
    $this->mockProvider = Mockery::mock(SmsProviderInterface::class);
    $this->mockProvider->shouldReceive('send')->andReturn(['success' => true, 'messageId' => 'test_id'])->byDefault();

    app()->instance(SmsProviderInterface::class, $this->mockProvider);

    $this->service = app(CampaignService::class);
});

test('it correctly counts every active customer for the "all" segment', function () {
    Customer::factory()->count(5)->create(['tenant_id' => $this->tenant->id, 'status' => 'active']);
    Customer::factory()->count(2)->create(['tenant_id' => $this->tenant->id, 'status' => 'inactive']);

    // Another tenant's customers
    Customer::factory()->count(3)->create(['tenant_id' => Tenant::factory()->create()->id, 'status' => 'active']);

    $count = $this->service->getAudienceCount($this->tenant, ['type' => 'all']);

    expect($count)->toBe(5);
});

test('it correctly filters active customers within 30 days', function () {
    Customer::factory()->create([
        'tenant_id'     => $this->tenant->id,
        'status'        => 'active',
        'last_visit_at' => now()->subDays(10),
    ]);

    Customer::factory()->create([
        'tenant_id'     => $this->tenant->id,
        'status'        => 'active',
        'last_visit_at' => now()->subDays(40),
    ]);

    $count = $this->service->getAudienceCount($this->tenant, ['type' => 'active', 'days' => 30]);

    expect($count)->toBe(1);
});

test('it correctly filters lapsed customers past 60 days', function () {
    Customer::factory()->create([
        'tenant_id'     => $this->tenant->id,
        'status'        => 'active',
        'last_visit_at' => now()->subDays(70),
    ]);

    Customer::factory()->create([
        'tenant_id'     => $this->tenant->id,
        'status'        => 'active',
        'last_visit_at' => now()->subDays(10),
    ]);

    Customer::factory()->create([
        'tenant_id'     => $this->tenant->id,
        'status'        => 'active',
        'last_visit_at' => null, // Never visited
    ]);

    $count = $this->service->getAudienceCount($this->tenant, ['type' => 'lapsed', 'days' => 60]);

    expect($count)->toBe(2);
});

test('it correctly calculates high-value 10% percentile threshold', function () {
    // Create 10 customers with varying points
    for ($i = 1; $i <= 10; $i++) {
        Customer::factory()->create([
            'tenant_id'              => $this->tenant->id,
            'status'                 => 'active',
            'lifetime_points_earned' => $i * 100, // 100, 200, ..., 1000
        ]);
    }

    // Top 10% (1 customer) should have >= 1000 points
    $count = $this->service->getAudienceCount($this->tenant, ['type' => 'high_value', 'percentile' => 10]);

    expect($count)->toBe(1);
});

test('it reserves sms credits and queues campaign if wallet has enough balance', function () {
    Customer::factory()->count(10)->create(['tenant_id' => $this->tenant->id, 'status' => 'active']);

    $message = 'Hello test campaign!'; // ~1 credit per recipient
    $result = $this->service->createAndDispatch(
        tenant: $this->tenant,
        createdByUserId: $this->user->id,
        name: 'Test Campaign',
        message: $message,
        segmentConfig: ['type' => 'all'],
    );

    expect($result['success'])->toBeTrue();
    expect($result['campaign'])->toBeInstanceOf(Campaign::class);

    $this->wallet->refresh();
    // Since jobs run synchronously in tests, reservation is already released
    expect($this->wallet->credits_reserved)->toBe(0);
    expect($this->wallet->credits_balance)->toBe(990); // 1000 - 10

    $this->assertDatabaseHas('campaigns', [
        'tenant_id'        => $this->tenant->id,
        'name'             => 'Test Campaign',
        'recipients_total' => 10,
        'recipients_sent'  => 10,
        'status'           => 'completed',
        'credits_used'     => 10,
    ]);
});

test('it fails to create campaign if wallet balance is insufficient', function () {
    Customer::factory()->count(10)->create(['tenant_id' => $this->tenant->id, 'status' => 'active']);

    $this->wallet->update(['credits_balance' => 5]); // Need 10

    $result = $this->service->createAndDispatch(
        tenant: $this->tenant,
        createdByUserId: $this->user->id,
        name: 'Poor Merchant Campaign',
        message: 'Hello',
        segmentConfig: ['type' => 'all'],
    );

    expect($result['success'])->toBeFalse();
    expect($result['error'])->toContain('Insufficient SMS credits');

    $this->wallet->refresh();
    expect($this->wallet->credits_reserved)->toBe(0);
});
