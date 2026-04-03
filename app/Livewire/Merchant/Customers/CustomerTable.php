<?php

namespace App\Livewire\Merchant\Customers;

use Livewire\Component;
use App\Models\Customer;
use Livewire\Attributes\On;
use Livewire\WithPagination;
use App\Services\TenantContext;
use Livewire\Attributes\Layout;

class CustomerTable extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = '';

    #[On('refresh-customers')]
    public function refresh()
    {
        // Simply triggers a re-render
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatus()
    {
        $this->resetPage();
    }

    #[Layout('layouts.admin')]
    public function render(TenantContext $tenantContext)
    {
        $tenant = $tenantContext->current();

        $customers = Customer::where('tenant_id', $tenant->id)
            ->latest()
            ->paginate(10);

        return view('livewire.merchant.customers.customer-table', [
            'customers' => $customers,
        ]);
    }
}
