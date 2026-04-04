<?php

namespace App\Livewire\Merchant\Customers;

use Livewire\Component;
use App\Models\Customer;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;

class CustomerProfile extends Component
{
    use WithPagination;

    public Customer $customer;

    public function mount(Customer $customer)
    {
        // Add basic tenant cross-check just to be safe, assuming TenantContext is correctly scoping
        // But the middleware handles basic tenant scoping. We'll rely on global scopes if they exist
        // or explicitly check it if needed. For now the model belongs to a tenant explicitly.
        $this->customer = $customer;
        $this->customer->loadCount('pointTransactions', 'redemptions');
    }

    #[On('points-awarded')]
    #[On('redemption-created')]
    public function refreshCustomer()
    {
        $this->customer->refresh();
        $this->customer->loadCount('pointTransactions', 'redemptions');
    }

    #[Layout('layouts.admin')]
    public function render()
    {
        $transactions = $this->customer->pointTransactions()
            ->latest()
            ->paginate(10);

        return view('livewire.merchant.customers.customer-profile', [
            'transactions' => $transactions,
        ]);
    }
}
