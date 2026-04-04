<?php

namespace App\Livewire\Merchant\Customers;

use Livewire\Component;
use App\Models\Customer;
use Livewire\Attributes\On;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

class CustomerProfile extends Component
{
    use WithPagination;

    public Customer $customer;

    public function mount(Customer $customer)
    {
        $this->customer = $customer;
        $this->customer->loadCount(['pointTransactions', 'redemptions', 'referrals']);
    }

    public string $tab = 'transactions';

    public function setTab(string $tab)
    {
        $this->tab = $tab;
    }

    #[On('points-awarded')]
    #[On('redemption-created')]
    public function refreshCustomer()
    {
        $this->customer->refresh();
        $this->customer->loadCount(['pointTransactions', 'redemptions', 'referrals']);
    }

    #[Layout('layouts.admin')]
    public function render()
    {
        $transactions = $this->customer->pointTransactions()
            ->latest()
            ->paginate(10, pageName: 'transactions-page');

        $referrals = $this->customer->referrals()
            ->with('referred')
            ->latest()
            ->paginate(10, pageName: 'referrals-page');

        return view('livewire.merchant.customers.customer-profile', [
            'transactions' => $transactions,
            'referrals'    => $referrals,
        ]);
    }
}
