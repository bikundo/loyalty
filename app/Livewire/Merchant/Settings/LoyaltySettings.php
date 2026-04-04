<?php

namespace App\Livewire\Merchant\Settings;

use Flux\Flux;
use Livewire\Component;
use App\Models\LoyaltyProgram;
use App\Services\TenantContext;
use Livewire\Attributes\Layout;

class LoyaltySettings extends Component
{
    public LoyaltyProgram $program;

    public int $referral_reward_points;

    public int $points_to_kes_ratio;

    public int $expiry_days;

    public function mount(TenantContext $tenantContext)
    {
        $tenant = $tenantContext->current();

        $this->program = LoyaltyProgram::firstOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'name'                => "{$tenant->name} Loyalty",
                'points_to_kes_ratio' => 1,
                'expiry_days'         => 365,
                'is_active'           => true,
            ]
        );

        $this->referral_reward_points = $this->program->referral_reward_points ?? 0;
        $this->points_to_kes_ratio = $this->program->points_to_kes_ratio;
        $this->expiry_days = $this->program->expiry_days;
    }

    public function save()
    {
        $this->validate([
            'referral_reward_points' => 'required|integer|min:0',
            'points_to_kes_ratio'    => 'required|integer|min:1',
            'expiry_days'            => 'required|integer|min:0',
        ]);

        $this->program->update([
            'referral_reward_points' => $this->referral_reward_points,
            'points_to_kes_ratio'    => $this->points_to_kes_ratio,
            'expiry_days'            => $this->expiry_days,
        ]);

        Flux::toast('Loyalty program settings updated successfully.');
    }

    #[Layout('layouts.admin')]
    public function render()
    {
        return view('livewire.merchant.settings.loyalty-settings');
    }
}
