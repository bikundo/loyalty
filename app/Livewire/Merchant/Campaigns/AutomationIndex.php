<?php

namespace App\Livewire\Merchant\Campaigns;

use Flux;
use App\Models\Tenant;
use Livewire\Component;
use App\Services\TenantContext;
use App\Models\CampaignAutomation;

class AutomationIndex extends Component
{
    public array $rules = [];

    public function mount(TenantContext $tenantContext)
    {
        $tenant = $tenantContext->current();
        $this->loadRules($tenant);
    }

    public function loadRules(Tenant $tenant)
    {
        $types = ['birthday', 'reward_milestone', 'lapsed_customer'];

        foreach ($types as $type) {
            $rule = CampaignAutomation::firstOrCreate(
                ['tenant_id' => $tenant->id, 'trigger_type' => $type],
                [
                    'name'             => ucfirst(str_replace('_', ' ', $type)),
                    'message_template' => $this->getDefaultTemplate($type),
                    'is_enabled'       => false,
                    'points_bonus'     => $type === 'birthday' ? 50 : 0,
                    'config'           => $this->getDefaultConfig($type),
                ]
            );

            $this->rules[$type] = [
                'id'               => $rule->id,
                'is_enabled'       => $rule->is_enabled,
                'message_template' => $rule->message_template,
                'points_bonus'     => $rule->points_bonus,
                'config'           => $rule->config,
            ];
        }
    }

    public function saveRule(string $type)
    {
        $data = $this->rules[$type];

        $rule = CampaignAutomation::find($data['id']);
        $rule->update([
            'is_enabled'       => $data['is_enabled'],
            'message_template' => $data['message_template'],
            'points_bonus'     => $data['points_bonus'],
            'config'           => $data['config'],
        ]);

        Flux::toast('Automation updated successfully.');
    }

    protected function getDefaultTemplate(string $type): string
    {
        return match ($type) {
            'birthday'         => "Happy Birthday {{name}}! We've gifted you {{points}} points. Celebrate with us today!",
            'reward_milestone' => "Hi {{name}}, you're only {{points_needed}} points away from your {{reward_name}}! See you soon.",
            'lapsed_customer'  => 'We miss you {{name}}! Visit us soon to keep your loyalty status active.',
            default            => 'Hi {{name}}!',
        };
    }

    protected function getDefaultConfig(string $type): array
    {
        return match ($type) {
            'reward_milestone' => ['milestone_threshold' => 0.9],
            'lapsed_customer'  => ['lapsed_days' => 30],
            default            => [],
        };
    }

    public function render()
    {
        return view('livewire.merchant.campaigns.automation-index');
    }
}
