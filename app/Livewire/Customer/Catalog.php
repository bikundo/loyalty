<?php

namespace App\Livewire\Customer;

use App\Models\Reward;
use Livewire\Component;
use App\Models\Customer;
use App\Services\TenantContext;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;

class Catalog extends Component
{
    /** @var Customer */
    public $customer;

    public function mount(TenantContext $tenantContext)
    {
        $this->customer = Auth::guard('customer')->user();

        if (!$this->customer) {
            // Fallback for development if not logged in
            // $this->customer = Customer::first();
        }
    }

    #[Layout('layouts.app')]
    public function render(TenantContext $tenantContext)
    {
        $tenant = $tenantContext->current();

        $rewards = Reward::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('points_required', 'asc')
            ->get();

        return view('livewire.customer.catalog', [
            'rewards' => $rewards,
            'points'  => $this->customer->total_points ?? 0,
        ]);
    }
}
