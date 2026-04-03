<?php

namespace App\Livewire\Merchant\Customers;

use App\Models\Customer;
use App\Services\AwardPointsService;
use App\Services\TenantContext;
use Livewire\Component;
use Livewire\Attributes\Validate;
use Flux\Flux;

class AwardPointsForm extends Component
{
    public Customer $customer;

    #[Validate('required|numeric|min:0')]
    public float $amount_spent_kes = 0;

    #[Validate('nullable|string|max:255')]
    public string $note = '';

    public function mount(Customer $customer)
    {
        $this->customer = $customer;
    }

    public function save(AwardPointsService $awardPointsService, TenantContext $tenantContext)
    {
        $this->validate();

        $tenant = $tenantContext->current();
        
        $createdTransactions = $awardPointsService->handle(
            $tenant, 
            $this->customer, 
            ['amount_spent_kes' => $this->amount_spent_kes],
            ['note' => $this->note, 'triggered_by' => 'dashboard']
        );

        if (empty($createdTransactions)) {
            Flux::toast('No points were awarded. Check active rules and minimum spend requirements.', variant: 'warning');
        } else {
            $total = collect($createdTransactions)->sum('points');
            Flux::toast("Successfully awarded $total points.");
        }

        $this->reset(['amount_spent_kes', 'note']);
        
        $this->modal('award-points-modal')->close();
        
        // Let the profile view know to refresh its balances
        $this->dispatch('points-awarded');
    }

    public function render()
    {
        return view('livewire.merchant.customers.award-points-form');
    }
}
