<?php

namespace App\Livewire\Merchant\Customers;

use Livewire\Component;
use App\Models\Customer;
use App\Services\TenantContext;
use Illuminate\Validation\Rule;
use App\Services\ReferralService;

class EnrolmentForm extends Component
{
    public string $name = '';

    public string $phone = '';

    public ?string $date_of_birth = null;

    public string $referral_code = '';

    public function save(TenantContext $tenantContext)
    {
        $tenant = $tenantContext->current();

        $this->validate([
            'name'  => 'required|string|max:255',
            'phone' => [
                'required',
                'string',
                'max:50',
                Rule::unique('customers', 'phone')->where('tenant_id', $tenant->id),
            ],
            'date_of_birth' => 'nullable|date',
            'referral_code' => 'nullable|string|max:20',
        ]);

        $customer = Customer::create([
            'tenant_id'         => $tenant->id,
            'name'              => $this->name,
            'phone'             => $this->phone,
            'date_of_birth'     => $this->date_of_birth,
            'enrolment_channel' => 'merchant_portal',
            'status'            => 'active',
            'enrolled_at'       => now(),
            'total_points'      => 0, // start with 0
        ]);

        if ($this->referral_code) {
            app(ReferralService::class)->link($customer, $this->referral_code);
        }

        $this->reset(['name', 'phone', 'date_of_birth', 'referral_code']);

        // Flux modal closing
        $this->dispatch('close-modal', 'enrol-customer');

        // Refresh the parent table
        $this->dispatch('refresh-customers')->to(CustomerTable::class);

        // Flash message or dispatch notification
        // For flux we can use Flux::toast if available, but let's dispatch a generic event or use session
    }

    public function render()
    {
        return view('livewire.merchant.customers.enrolment-form');
    }
}
