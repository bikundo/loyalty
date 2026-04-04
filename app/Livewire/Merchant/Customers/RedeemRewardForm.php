<?php

namespace App\Livewire\Merchant\Customers;

use Exception;
use Flux\Flux;
use App\Models\Reward;
use Livewire\Component;
use App\Models\Customer;
use App\Services\TenantContext;
use App\Services\RedemptionService;
use Illuminate\Support\Facades\Auth;

class RedeemRewardForm extends Component
{
    public Customer $customer;

    public $rewards = [];

    public function mount(Customer $customer, TenantContext $tenantContext)
    {
        $this->customer = $customer;
        $this->loadRewards($tenantContext);
    }

    public function loadRewards(TenantContext $tenantContext)
    {
        $tenant = $tenantContext->current();

        $this->rewards = Reward::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('points_required', 'asc')
            ->get();
    }

    public function redeem(Reward $reward, RedemptionService $redemptionService, TenantContext $tenantContext)
    {
        $tenant = $tenantContext->current();

        try {
            $redemptionService->handle($tenant, $this->customer, $reward, [
                'triggered_by' => 'merchant_portal',
                'user_id'      => \Illuminate\Support\Facades\Auth::id(),
            ]);

            Flux::toast(
                text: "Successfully redeemed {$reward->name} for {$this->customer->name}.",
                variant: 'success'
            );

            $this->dispatch('redemption-created');
            $this->dispatch('close-modal', name: 'redeem-reward-modal');

            // Refresh local state
            $this->customer->refresh();
            $this->loadRewards($tenantContext);

        }
        catch (Exception $e) {
            Flux::toast(
                text: 'Redemption failed: ' . $e->getMessage(),
                variant: 'danger'
            );
        }
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.merchant.customers.redeem-reward-form');
    }
}
