<?php

namespace App\Livewire\Merchant\Customers;

use App\Models\Customer;
use App\Models\Reward;
use App\Services\RedemptionService;
use App\Services\TenantContext;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Validate;
use Flux\Flux;

class RedeemRewardForm extends Component
{
    public Customer $customer;

    #[Validate('required|exists:rewards,id')]
    public ?int $selectedRewardId = null;

    public function mount(Customer $customer)
    {
        $this->customer = $customer;
    }

    public function redeem(RedemptionService $redemptionService)
    {
        $this->validate();

        $reward = Reward::findOrFail($this->selectedRewardId);

        // Security check: ensure reward belongs to the same tenant as the customer
        if ($reward->tenant_id !== $this->customer->tenant_id) {
            Flux::toast("Unauthorized reward selection.", variant: 'danger');
            return;
        }

        if ($this->customer->total_points < $reward->points_required) {
            $this->addError('selectedRewardId', "Insufficient points to redeem this reward.");
            return;
        }

        try {
            $redemptionService->redeem($this->customer, $reward, Auth::id());
            
            $this->dispatch('close-modal', name: 'redeem-reward-modal');
            
            Flux::toast(
                text: "Successfully redeemed: {$reward->name}",
                variant: 'success'
            );
            
            $this->selectedRewardId = null;
            
            // Notify the profile to refresh
            $this->dispatch('redemption-created');
        } catch (\Exception $e) {
            Flux::toast(
                text: "Redemption failed: " . $e->getMessage(),
                variant: 'danger'
            );
        }
    }

    public function render(TenantContext $tenantContext)
    {
        $tenant = $tenantContext->current();
        
        // Fetch active rewards for the tenant's loyalty program
        $rewards = Reward::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            })
            ->orderBy('points_required', 'asc')
            ->get();

        return view('livewire.merchant.customers.redeem-reward-form', [
            'rewards' => $rewards,
        ]);
    }
}
