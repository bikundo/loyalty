<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Reward;
use App\Models\Tenant;
use App\Models\Customer;
use App\Models\PointTransaction;
use App\Models\CampaignAutomation;
use App\Services\AutomationService;
use App\Services\AwardPointsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AutomationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AutomationService $service;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AutomationService::class);
        $this->tenant = Tenant::factory()->create();

        // Ensure tenant has SMS credits for tests
        $this->tenant->smsWallet()->updateOrCreate(
            ['tenant_id' => $this->tenant->id],
            ['credits_balance' => 1000]
        );
    }

    public function test_it_processes_birthday_rewards()
    {
        // 1. Setup Automation Rule
        $automation = CampaignAutomation::create([
            'tenant_id'        => $this->tenant->id,
            'trigger_type'     => 'birthday',
            'name'             => 'Birthday Rule',
            'message_template' => 'Happy Birthday {{name}}!',
            'points_bonus'     => 50,
            'is_enabled'       => true,
        ]);

        // 2. Setup Customer with Birthday Today
        $customer = Customer::factory()->create([
            'tenant_id'     => $this->tenant->id,
            'date_of_birth' => now(), // today
        ]);

        // 3. Process
        $this->service->runTimeBasedAutomations();

        // 4. Assertions
        $this->assertDatabaseHas('point_transactions', [
            'customer_id' => $customer->id,
            'points'      => 50,
            'type'        => 'earn',
        ]);

        $this->assertDatabaseHas('automation_logs', [
            'customer_id'            => $customer->id,
            'campaign_automation_id' => $automation->id,
            'year_dispatched'        => now()->format('Y'),
        ]);

        // Assert SMS was logged (mocking or checking DB)
        $this->assertDatabaseHas('sms_logs', [
            'customer_id' => $customer->id,
            'message'     => "Happy Birthday {$customer->name}!",
        ]);
    }

    public function test_it_does_not_double_send_birthdays()
    {
        $automation = CampaignAutomation::create([
            'tenant_id'        => $this->tenant->id,
            'trigger_type'     => 'birthday',
            'name'             => 'Birthday Rule',
            'message_template' => 'Hi',
            'points_bonus'     => 50,
            'is_enabled'       => true,
        ]);

        $customer = Customer::factory()->create([
            'tenant_id'     => $this->tenant->id,
            'date_of_birth' => now(),
        ]);

        // Run twice
        $this->service->runTimeBasedAutomations();
        $this->service->runTimeBasedAutomations();

        // Expect only 1 transaction
        $this->assertEquals(1, PointTransaction::where('customer_id', $customer->id)->count());
    }

    public function test_it_triggers_milestone_nudges()
    {
        $automation = CampaignAutomation::create([
            'tenant_id'        => $this->tenant->id,
            'trigger_type'     => 'reward_milestone',
            'name'             => 'Milestone Rule',
            'message_template' => 'Almost {{reward_name}}!',
            'config'           => ['milestone_threshold' => 0.9],
            'is_enabled'       => true,
        ]);

        $reward = Reward::factory()->create([
            'tenant_id'       => $this->tenant->id,
            'points_required' => 100,
            'name'            => 'Free Coffee',
        ]);

        $customer = Customer::factory()->create([
            'tenant_id'    => $this->tenant->id,
            'total_points' => 85, // Not quite 90 today
        ]);

        // Award 5 points -> total 90 (90%)
        app(AwardPointsService::class)->awardBonus($this->tenant, $customer, 5, 'Daily visit');

        // Milestone nudge should trigger
        $this->assertDatabaseHas('sms_logs', [
            'customer_id' => $customer->id,
            'message'     => 'Almost Free Coffee!',
        ]);
    }
}
