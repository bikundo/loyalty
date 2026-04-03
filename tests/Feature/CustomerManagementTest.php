<?php

use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Livewire\Merchant\Customers\EnrolmentForm;
use App\Livewire\Merchant\Customers\CustomerTable;


it('renders the customer table component', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    app(\App\Services\TenantContext::class)->set($tenant);

    Livewire::actingAs($user)
        ->test(CustomerTable::class)
        ->assertStatus(200);
});

it('can enrol a new customer via form', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    app(\App\Services\TenantContext::class)->set($tenant);

    Livewire::actingAs($user)
        ->test(EnrolmentForm::class)
        ->set('name', 'John Doe')
        ->set('phone', '0712345678')
        ->set('date_of_birth', '1990-01-01')
        ->call('save')
        ->assertDispatched('close-modal', 'enrol-customer')
        ->assertDispatched('refresh-customers');

    expect(Customer::where('tenant_id', $tenant->id)->count())->toBe(1);
    
    $customer = Customer::first();
    // Since name and phone are encrypted, we decrypt/check their original value using model attributes
    expect($customer->name)->toBe('John Doe');
    expect($customer->phone)->toBe('0712345678');
    expect($customer->date_of_birth->format('Y-m-d'))->toBe('1990-01-01');
    expect($customer->tenant_id)->toBe($tenant->id);
});

it('prevents naive duplicate phone numbers within the same tenant', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    
    // Seed an existing customer
    Customer::factory()->create([
        'tenant_id' => $tenant->id,
        'phone' => '0712345678',
        'name' => 'Existing User'
    ]);

    app(\App\Services\TenantContext::class)->set($tenant);

    Livewire::actingAs($user)
        ->test(EnrolmentForm::class)
        ->set('name', 'Jane Doe')
        ->set('phone', '0712345678')
        ->call('save')
        ->assertHasErrors(['phone']);

    expect(Customer::where('tenant_id', $tenant->id)->count())->toBe(1);
});
