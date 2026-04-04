<?php

namespace App\Services;

use App\Models\Reward;
use App\Models\Tenant;
use App\Models\Customer;
use App\Models\AutomationLog;
use App\Models\PointTransaction;
use App\Services\Sms\SmsService;
use App\Models\CampaignAutomation;
use Illuminate\Support\Facades\DB;

class AutomationService
{
    public function __construct(
        protected SmsService $smsService,
        protected AwardPointsService $awardPointsService
    ) {}

    /**
     * Run daily time-based automations (Birthdays, Lapsed).
     */
    public function runTimeBasedAutomations(): void
    {
        Tenant::chunk(50, function ($tenants) {
            foreach ($tenants as $tenant) {
                $this->processBirthdays($tenant);
                $this->processLapsedCustomers($tenant);
            }
        });
    }

    /**
     * Process birthdays for a tenant.
     */
    protected function processBirthdays(Tenant $tenant): void
    {
        $automation = $tenant->automations()
            ->where('trigger_type', 'birthday')
            ->where('is_enabled', true)
            ->first();

        if (!$automation) {
            return;
        }

        $today = now();
        $year = $today->format('Y');

        // Find customers with birthday today who haven't received the gift this year
        Customer::where('tenant_id', $tenant->id)
            ->whereMonth('date_of_birth', $today->month)
            ->whereDay('date_of_birth', $today->day)
            ->whereDoesntHave('pointTransactions', function ($q) use ($year) {
                $q->where('type', 'earn')
                    ->where('note', 'like', "Birthday Bonus ($year)%");
            })
            ->chunk(100, function ($customers) use ($tenant, $automation, $year) {
                foreach ($customers as $customer) {
                    $this->triggerBirthdayReward($tenant, $customer, $automation, $year);
                }
            });
    }

    /**
     * Trigger the birthday award.
     */
    protected function triggerBirthdayReward(Tenant $tenant, Customer $customer, CampaignAutomation $automation, string $year): void
    {
        DB::transaction(function () use ($tenant, $customer, $automation, $year) {
            // 1. Award points if configured
            if ($automation->points_bonus > 0) {
                $this->awardPointsService->awardBonus(
                    $tenant,
                    $customer,
                    $automation->points_bonus,
                    "Birthday Bonus ($year)"
                );
            }

            // 2. Send SMS
            $message = $this->parseTemplate($automation->message_template, [
                'name'   => $customer->name,
                'points' => $automation->points_bonus,
            ]);

            $this->smsService->sendToCustomer($customer, $message, [
                'automation_id' => $automation->id,
                'trigger'       => 'birthday',
            ]);

            // 3. Log execution (using PointTransaction as the primary record for points,
            // but we could also use AutomationLog for secondary metrics)
            AutomationLog::create([
                'tenant_id'              => $tenant->id,
                'customer_id'            => $customer->id,
                'campaign_automation_id' => $automation->id,
                'year_dispatched'        => $year,
                'dispatched_at'          => now(),
            ]);
        });
    }

    /**
     * Process lapsed customers for a tenant.
     */
    protected function processLapsedCustomers(Tenant $tenant): void
    {
        $automation = $tenant->automations()
            ->where('trigger_type', 'lapsed_customer')
            ->where('is_enabled', true)
            ->first();

        if (!$automation) {
            return;
        }

        $days = (int) ($automation->config['lapsed_days'] ?? 30);
        $threshold = now()->subDays($days)->startOfDay();

        // Target: Customers whose last visit was exactly $days ago
        Customer::where('tenant_id', $tenant->id)
            ->whereDate('last_visit_at', $threshold)
            ->chunk(100, function ($customers) use ($tenant, $automation) {
                foreach ($customers as $customer) {
                    $this->triggerLapsedReminder($tenant, $customer, $automation);
                }
            });
    }

    protected function triggerLapsedReminder(Tenant $tenant, Customer $customer, CampaignAutomation $automation): void
    {
        $message = $this->parseTemplate($automation->message_template, [
            'name' => $customer->name,
        ]);

        $this->smsService->sendToCustomer($customer, $message, [
            'automation_id' => $automation->id,
            'trigger'       => 'lapsed',
        ]);

        AutomationLog::create([
            'tenant_id'              => $tenant->id,
            'customer_id'            => $customer->id,
            'campaign_automation_id' => $automation->id,
            'dispatched_at'          => now(),
        ]);
    }

    /**
     * Evaluate milestones after a customer earns points.
     */
    public function evaluateMilestones(Customer $customer): void
    {
        $tenant = $customer->tenant;
        $automation = $tenant->automations()
            ->where('trigger_type', 'reward_milestone')
            ->where('is_enabled', true)
            ->first();

        if (!$automation) {
            return;
        }

        $threshold = (float) ($automation->config['milestone_threshold'] ?? 0.9); // e.g. 90%

        // Find the closest reward the customer almost has
        $reward = Reward::where('tenant_id', $tenant->id)
            ->where('points_required', '>', $customer->total_points)
            ->orderBy('points_required', 'asc')
            ->first();

        if (!$reward) {
            return;
        }

        $progress = $customer->total_points / $reward->points_required;

        if ($progress >= $threshold) {
            // Check if we already nudged them for THIS reward in the last 30 days
            $alreadyNudged = AutomationLog::where('customer_id', $customer->id)
                ->where('campaign_automation_id', $automation->id)
                ->where('dispatched_at', '>=', now()->subDays(30))
                ->exists();

            if (!$alreadyNudged) {
                $this->triggerMilestoneNudge($tenant, $customer, $automation, $reward);
            }
        }
    }

    protected function triggerMilestoneNudge(Tenant $tenant, Customer $customer, CampaignAutomation $automation, Reward $reward): void
    {
        $message = $this->parseTemplate($automation->message_template, [
            'name'          => $customer->name,
            'reward_name'   => $reward->name,
            'points_needed' => $reward->points_required - $customer->total_points,
        ]);

        $this->smsService->sendToCustomer($customer, $message, [
            'automation_id' => $automation->id,
            'trigger'       => 'milestone',
        ]);

        AutomationLog::create([
            'tenant_id'              => $tenant->id,
            'customer_id'            => $customer->id,
            'campaign_automation_id' => $automation->id,
            'dispatched_at'          => now(),
        ]);
    }

    /**
     * Simple template parser.
     */
    protected function parseTemplate(string $template, array $data): string
    {
        foreach ($data as $key => $value) {
            $template = str_replace("{{{$key}}}", $value, $template);
        }

        return $template;
    }
}
