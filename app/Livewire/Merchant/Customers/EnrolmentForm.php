<?php

namespace App\Livewire\Merchant\Customers;

use Livewire\Component;
use App\Models\Customer;
use App\Services\TenantContext;
use Illuminate\Validation\Rule;

class EnrolmentForm extends Component
{
    public string $name = '';

    public string $phone = '';

    public ?string $date_of_birth = null;

    public function save(TenantContext $tenantContext)
    {
        $tenant = $tenantContext->current();

        $this->validate([
            'name'  => 'required|string|max:255',
            'phone' => [
                'required',
                'string',
                'max:50',
                // Cannot easily use database unique rule because phone is encrypted.
                // We'll need to fetch and compare, or just allow it and let the database
                // fail if it's uniquely indexed with a blind index, but for this basic iteration
                // we'll just check if it exists in the collection as an example, though in real life
                // one would use deterministic encryption or blind index for unique check.
                // For now, let's keep it simple.
            ],
            'date_of_birth' => 'nullable|date',
        ]);

        // Attempt to prevent naive duplicates in memory
        $exists = Customer::where('tenant_id', $tenant->id)->get()->contains('phone', $this->phone);

        if ($exists) {
            $this->addError('phone', 'This phone number is already enrolled.');

            return;
        }

        Customer::create([
            'tenant_id'         => $tenant->id,
            'name'              => $this->name,
            'phone'             => $this->phone,
            'date_of_birth'     => $this->date_of_birth,
            'enrolment_channel' => 'merchant_portal',
            'status'            => 'active',
            'enrolled_at'       => now(),
            'total_points'      => 0, // start with 0
        ]);

        $this->reset(['name', 'phone', 'date_of_birth']);

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
