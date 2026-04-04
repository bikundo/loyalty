<?php

namespace App\Livewire\Merchant\Settings;

use Flux\Flux;
use Livewire\Component;
use App\Models\Cashier;
use App\Models\TenantLocation;
use App\Services\TenantContext;
use Livewire\Attributes\Layout;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

class StaffManagement extends Component
{
    public $staffId;
    public $name;
    public $pin;
    public $locationId;
    public $dailyCap = 50000;
    public $isActive = true;

    public $showStaffModal = false;

    protected function rules()
    {
        return [
            'name'       => 'required|string|max:255',
            'pin'        => [$this->staffId ? 'nullable' : 'required', 'digits_between:4,8'],
            'locationId' => 'nullable|exists:tenant_locations,id',
            'dailyCap'   => 'required|integer|min:0',
            'isActive'   => 'boolean',
        ];
    }

    #[Layout('layouts.admin')]
    public function render(TenantContext $tenantContext)
    {
        $tenant = $tenantContext->current();

        return view('livewire.merchant.settings.staff-management', [
            'staff'     => Cashier::where('tenant_id', $tenant->id)->with('location')->get(),
            'locations' => TenantLocation::where('tenant_id', $tenant->id)->get(),
        ]);
    }

    public function createStaff()
    {
        $this->reset(['staffId', 'name', 'pin', 'locationId', 'dailyCap', 'isActive']);
        $this->showStaffModal = true;
    }

    public function editStaff(Cashier $staff)
    {
        $this->staffId    = $staff->id;
        $this->name       = $staff->name;
        $this->locationId = $staff->tenant_location_id;
        $this->dailyCap   = $staff->daily_award_cap_kes;
        $this->isActive   = $staff->is_active;
        $this->pin        = ''; // Don't show hashed PIN

        $this->showStaffModal = true;
    }

    public function saveStaff(TenantContext $tenantContext)
    {
        $this->validate();

        $tenant = $tenantContext->current();

        $data = [
            'tenant_id'           => $tenant->id,
            'tenant_location_id'  => $this->locationId,
            'name'                => $this->name,
            'daily_award_cap_kes' => $this->dailyCap,
            'is_active'           => $this->isActive,
        ];

        if ($this->pin) {
            $data['pin'] = Hash::make($this->pin);
        }

        Cashier::updateOrCreate(['id' => $this->staffId], $data);

        $this->showStaffModal = false;
        
        Flux::toast(
            $this->staffId ? 'Staff updated successfully.' : 'Staff created successfully.'
        );
    }

    public function toggleStatus(Cashier $staff)
    {
        $staff->update(['is_active' => !$staff->is_active]);
        Flux::toast('Staff status updated.');
    }
}
