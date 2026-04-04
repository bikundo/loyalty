<?php

use App\Models\Tenant;
use App\Models\Customer;
use App\Models\Reward;
use App\Models\LoyaltyRule;
use App\Services\AwardPointsService;
use App\Services\RedemptionService;
use App\Jobs\ExpireStalePointsJob;
use App\Models\PointTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    
    // Create loyalty program manually to avoid null relation
    $this->tenant->loyaltyProgram()->create([
        'uuid'                => \Illuminate\Support\Str::uuid()->toString(),
        'name'                => $this->tenant->name . ' Loyalty',
        'expiry_days'         => 30, // 30 days
        'points_to_kes_ratio' => 1,
        'is_active'           => true,
    ]);

    $this->rule = LoyaltyRule::create([
        'tenant_id' => $this->tenant->id,
        'loyalty_program_id' => $this->tenant->loyaltyProgram->id,
        'name' => 'Spend Rule',
        'type' => 'spend',
        'config' => ['points_per_kes' => 1, 'min_spend_kes' => 1],
        'is_active' => true,
    ]);

    $this->customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
    
    // Mock SMS provider to avoid real requests
    $this->app->bind(\App\Services\Sms\SmsProviderInterface::class, function () {
        return new class implements \App\Services\Sms\SmsProviderInterface {
            public function send(string $to, string $message, Tenant $tenant): array {
                return ['success' => true, 'messageId' => 'test-id'];
            }
        };
    });
});

it('sets expires_at and points_remaining on award', function () {
    $transactions = app(AwardPointsService::class)->handle($this->tenant, $this->customer, [
        'amount_spent_kes' => 100
    ]);

    $tx = $transactions[0];
    
    expect($tx->points)->toBe(100)
        ->and($tx->points_remaining)->toBe(100)
        ->and($tx->expires_at->toDateString())->toBe(now()->addDays(30)->toDateString());
});

it('depletes points_remaining in FIFO order during redemption', function () {
    // 1. Award 100 points (older)
    $tx1 = app(AwardPointsService::class)->handle($this->tenant, $this->customer, ['amount_spent_kes' => 100])[0];
    
    // 2. Award 50 points (newer)
    $tx2 = app(AwardPointsService::class)->handle($this->tenant, $this->customer, ['amount_spent_kes' => 50])[0];
    
    // 3. Redeem 120 points
    $reward = Reward::factory()->create([
        'tenant_id' => $this->tenant->id,
        'points_required' => 120
    ]);
    
    app(RedemptionService::class)->handle($this->tenant, $this->customer, $reward);
    
    // 4. Verify FIFO: tx1 should be fully used (0 remaining), tx2 should have 30 left (50 - 20)
    expect($tx1->fresh()->points_remaining)->toBe(0)
        ->and($tx2->fresh()->points_remaining)->toBe(30)
        ->and($this->customer->fresh()->total_points)->toBe(30);
});

it('expires stale points correctly via job', function () {
    // 1. Award 100 points
    $tx = app(AwardPointsService::class)->handle($this->tenant, $this->customer, ['amount_spent_kes' => 100])[0];
    
    // 2. Manually set to expired
    $tx->update(['expires_at' => now()->subDay()]);
    
    // 3. Run job
    app(ExpireStalePointsJob::class)->handle();
    
    // 4. Verify balance is 0 and an 'expire' transaction exists
    expect($this->customer->fresh()->total_points)->toBe(0)
        ->and($tx->fresh()->points_remaining)->toBe(0);
        
    $this->assertDatabaseHas('point_transactions', [
        'customer_id' => $this->customer->id,
        'type' => 'expire',
        'points' => 100
    ]);
});
