<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Tenant;
use App\Models\Customer;
use App\Models\LoyaltyRule;
use App\Models\LoyaltyProgram;
use App\Services\ReferralService;
use App\Services\AwardPointsService;
use App\Services\Sms\SmsProviderInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReferralServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ReferralService $service;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock SMS Provider for testing
        $this->app->bind(SmsProviderInterface::class, function () {
            return new class() implements SmsProviderInterface
            {
                public function send(string $to, string $message, Tenant $tenant): array
                {
                    return ['success' => true, 'messageId' => 'test-id'];
                }
            };
        });

        $this->service = app(ReferralService::class);
        $this->tenant = Tenant::factory()->create();

        // Setup Loyalty Program with Referral Reward
        LoyaltyProgram::create([
            'tenant_id'              => $this->tenant->id,
            'name'                   => 'Test Program',
            'referral_reward_points' => 100,
            'is_active'              => true,
        ]);

        $this->tenant->refresh();

        $this->tenant->smsWallet()->updateOrCreate(
            ['tenant_id' => $this->tenant->id],
            ['credits_balance' => 1000]
        );
    }

    public function test_it_generates_referral_code_on_creation()
    {
        $customer = Customer::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'John Doe',
            'phone'     => '0712345678',
        ]);

        $this->assertNotNull($customer->referral_code);
        $this->assertStringContainsString('JOHN-', $customer->referral_code);
    }

    public function test_it_links_referral_via_code()
    {
        $referrer = Customer::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Alice']);
        $referee = Customer::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Bob']);

        $res = $this->service->link($referee, $referrer->referral_code);

        $this->assertTrue($res);
        $this->assertEquals($referrer->id, $referee->fresh()->referred_by_customer_id);

        $this->assertDatabaseHas('customer_referrals', [
            'referrer_customer_id' => $referrer->id,
            'referred_customer_id' => $referee->id,
            'status'               => 'pending',
        ]);
    }

    public function test_it_qualifies_referral_on_first_visit()
    {
        $referrer = Customer::factory()->create(['tenant_id' => $this->tenant->id, 'total_points' => 0]);
        $referee = Customer::factory()->create(['tenant_id' => $this->tenant->id, 'total_points' => 0]);

        $this->service->link($referee, $referrer->referral_code);

        // Create a simple rule for handle() to work
        LoyaltyRule::create([
            'loyalty_program_id' => $this->tenant->loyaltyProgram->id,
            'tenant_id'          => $this->tenant->id,
            'name'               => 'General Rule',
            'type'               => 'spend',
            'config'             => [
                'points_per_kes' => 1,
                'min_spend_kes'  => 1,
            ],
            'is_active' => true,
        ]);

        // Award first points to referee via handle() to increment visits
        app(AwardPointsService::class)->handle(
            $this->tenant,
            $referee,
            ['amount_spent_kes' => 10],
            ['triggered_by'     => 'test']
        );

        $this->assertDatabaseHas('customer_referrals', [
            'referrer_customer_id' => $referrer->id,
            'referred_customer_id' => $referee->id,
            'status'               => 'qualified',
        ]);

        // Referrer should get 100 points
        $this->assertEquals(100, $referrer->fresh()->total_points);

        $this->assertDatabaseHas('customer_referrals', [
            'referrer_customer_id' => $referrer->id,
            'referred_customer_id' => $referee->id,
            'status'               => 'qualified',
        ]);

        $this->assertDatabaseHas('sms_logs', [
            'customer_id' => $referrer->id,
            'status'      => 'sent',
        ]);
    }

    public function test_it_prevents_self_referral()
    {
        $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);

        $res = $this->service->link($customer, $customer->referral_code);

        $this->assertFalse($res);
    }
}
