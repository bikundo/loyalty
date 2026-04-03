<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Plan;
use App\Models\User;
use App\Models\Reward;
use App\Models\Tenant;
use App\Models\Customer;
use App\Models\SmsWallet;
use Illuminate\Support\Str;
use App\Models\LoyaltyProgram;
use App\Models\TenantSettings;
use Illuminate\Database\Seeder;
use App\Models\PointTransaction;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Platform Admin
        User::factory()->superAdmin()->create([
            'name'  => 'Platform Admin',
            'email' => 'admin@loyaltyos.africa',
        ]);

        // 2. Fetch Plans
        $starterPlan = Plan::where('slug', 'starter')->first();
        $growthPlan = Plan::where('slug', 'growth')->first();

        // 3. Setup MOMBASA COFFEE
        $this->setupTenant(
            name: 'Mombasa Coffee',
            subdomain: 'mombasa-coffee',
            plan: $growthPlan,
            ownerEmail: 'mombasa@example.com',
            customerCount: 50
        );

        // 4. Setup NAIROBI BARBERS
        $this->setupTenant(
            name: 'Nairobi Barbers',
            subdomain: 'nairobi-barbers',
            plan: $starterPlan,
            ownerEmail: 'nairobi@example.com',
            customerCount: 20
        );
    }

    private function setupTenant(string $name, string $subdomain, Plan $plan, string $ownerEmail, int $customerCount): void
    {
        $tenant = Tenant::factory()->create([
            'name'      => $name,
            'slug'      => Str::slug($name),
            'subdomain' => $subdomain,
            'plan_id'   => $plan->id,
        ]);

        $owner = User::factory()->forTenant($tenant)->create([
            'name'  => $name . ' Owner',
            'email' => $ownerEmail,
        ]);
        $owner->assignRole(Role::MerchantOwner->value);
        $tenant->update(['owner_user_id' => $owner->id]);

        TenantSettings::create([
            'tenant_id'      => $tenant->id,
            'programme_name' => $name . ' Rewards',
            'points_name'    => 'Beans',
        ]);

        LoyaltyProgram::create([
            'tenant_id' => $tenant->id,
            'name'      => $name . ' Loyalty',
        ]);

        SmsWallet::create(['tenant_id' => $tenant->id, 'credits_balance' => 1000]);

        // Create Rewards
        Reward::factory()->count(5)->create(['tenant_id' => $tenant->id]);

        // Create Customers and Transactions
        Customer::factory()->count($customerCount)->create(['tenant_id' => $tenant->id])
            ->each(function (Customer $customer) use ($tenant) {
                PointTransaction::factory()->count(3)->create([
                    'tenant_id'   => $tenant->id,
                    'customer_id' => $customer->id,
                    'type'        => 'earn',
                ]);
            });
    }
}
