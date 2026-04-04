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

    public function redeem(RedemptionService $redemptionService, TenantContext $tenantContext)
    {
        $this->validate();

        $reward = Reward::findOrFail($this->selectedRewardId);

        if ($this->customer->total_points < $reward->points_required) {
            $this->addError('selectedRewardId', "Insufficient points to redeem this reward.");
            return;
        }

        try {
            $redemptionService->redeem($this->customer, $reward, Auth::id());
            
            Flux::toast("Successfully redeemed: {$reward->name}.");
            
            $this->reset('selectedRewardId');
            
            $this->modal('redeem-reward-modal')->close();
            
            // Notify the parent (CustomerProfile) to refresh
            $this->dispatch('redemption-created');
        } catch (\Exception $e) {
            Flux::toast($e->getMessage(), variant: 'danger');
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
