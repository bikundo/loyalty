<?php

namespace App\Livewire\Merchant\Customers;

use App\Models\Customer;
use App\Services\AwardPointsService;
use App\Services\TenantContext;
use Livewire\Component;
use Livewire\Attributes\Validate;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;

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
        
        try {
            $createdTransactions = $awardPointsService->handle(
                $tenant, 
                $this->customer, 
                ['amount_spent_kes' => floatval($this->amount_spent_kes)],
                [
                    'note' => $this->note, 
                    'triggered_by' => 'merchant_portal',
                    'user_id' => Auth::id()
                ]
            );

            if (empty($createdTransactions)) {
                Flux::toast(
                    text: 'No points were awarded. Check active rules and minimum spend requirements.', 
                    variant: 'warning'
                );
            } else {
                $total = collect($createdTransactions)->sum('points');
                Flux::toast(
                    text: "Successfully awarded $total points to {$this->customer->name}.",
                    variant: 'success'
                );
                
                // Let the profile view know to refresh its balances
                $this->dispatch('points-awarded');
            }

            $this->reset(['amount_spent_kes', 'note']);
            $this->dispatch('close-modal', name: 'award-points-modal');

        } catch (\Exception $e) {
            Flux::toast(
                text: "Failed to award points: " . $e->getMessage(),
                variant: 'danger'
            );
        }
    }

    public function render()
    {
        return view('livewire.merchant.customers.award-points-form');
    }
}
