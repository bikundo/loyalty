<?php

namespace App\Livewire\Merchant\Rewards;

use Flux\Flux;
use App\Models\Reward;
use Livewire\Component;
use App\Models\LoyaltyProgram;
use App\Services\TenantContext;
use Livewire\Attributes\Layout;

class RewardManagement extends Component
{
    public $rewardId;

    public $name;

    public $description;

    public $type = 'item';

    public $pointsRequired = 100;

    public $discountValueKes;

    public $discountPercentage;

    public $isActive = true;

    public $showRewardModal = false;

    protected function rules()
    {
        return [
            'name'                => 'required|string|max:255',
            'description'         => 'nullable|string',
            'type'                => 'required|in:item,discount_fixed,discount_percent',
            'pointsRequired'      => 'required|integer|min:1',
            'discountValueKes'    => 'required_if:type,discount_fixed|nullable|integer|min:0',
            'discountPercentage'  => 'required_if:type,discount_percent|nullable|integer|min:0|max:100',
            'isActive'            => 'boolean',
        ];
    }

    #[Layout('layouts.admin')]
    public function render(TenantContext $tenantContext)
    {
        $tenant = $tenantContext->current();

        return view('livewire.merchant.rewards.reward-management', [
            'rewards' => Reward::where('tenant_id', $tenant->id)->orderBy('sort_order')->get(),
        ]);
    }

    public function createReward()
    {
        $this->reset(['rewardId', 'name', 'description', 'type', 'pointsRequired', 'discountValueKes', 'discountPercentage', 'isActive']);
        $this->showRewardModal = true;
    }

    public function editReward(Reward $reward)
    {
        $this->rewardId = $reward->id;
        $this->name = $reward->name;
        $this->description = $reward->description;
        $this->type = $reward->type;
        $this->pointsRequired = $reward->points_required;
        $this->discountValueKes = $reward->discount_value_kes;
        $this->discountPercentage = $reward->discount_percentage;
        $this->isActive = $reward->is_active;

        $this->showRewardModal = true;
    }

    public function saveReward(TenantContext $tenantContext)
    {
        $this->validate();

        $tenant = $tenantContext->current();
        $program = LoyaltyProgram::where('tenant_id', $tenant->id)->first();

        if (!$program) {
            Flux::toast(text: 'No active loyalty program found for this merchant.', variant: 'danger');

            return;
        }

        $data = [
            'tenant_id'            => $tenant->id,
            'loyalty_program_id'   => $program->id,
            'name'                 => $this->name,
            'description'          => $this->description,
            'type'                 => $this->type,
            'points_required'      => $this->pointsRequired,
            'discount_value_kes'   => $this->discountValueKes,
            'discount_percentage'  => $this->discountPercentage,
            'is_active'            => $this->isActive,
        ];

        Reward::updateOrCreate(['id' => $this->rewardId], $data);

        $this->showRewardModal = false;

        Flux::toast(
            text: $this->rewardId ? 'Reward updated successfully.' : 'Reward created successfully.',
            variant: 'success'
        );
    }

    public function toggleStatus(Reward $reward)
    {
        $reward->update(['is_active' => !$reward->is_active]);
        Flux::toast(text: 'Reward status updated.', variant: 'success');
    }

    public function deleteReward(Reward $reward)
    {
        $reward->delete();
        Flux::toast(text: 'Reward deleted successfully.', variant: 'success');
    }
}
