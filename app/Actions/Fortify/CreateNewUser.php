<?php

namespace App\Actions\Fortify;

use App\Models\Plan;
use App\Models\User;
use App\Models\Tenant;
use App\Models\SmsWallet;
use Illuminate\Support\Str;
use App\Models\LoyaltyProgram;
use App\Models\TenantSettings;
use App\Enums\Role as RoleEnum;
use Illuminate\Support\Facades\DB;
use App\Concerns\ProfileValidationRules;
use App\Concerns\PasswordValidationRules;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;
    use ProfileValidationRules;

    /**
     * Validate and create a newly registered merchant and their tenant.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password'      => $this->passwordRules(),
            'business_name' => ['required', 'string', 'max:255', 'unique:tenants,name'],
        ])->validate();

        return DB::transaction(function () use ($input) {
            // 1. Resolve Starter Plan
            $plan = Plan::where('slug', 'starter')->firstOrFail();

            // 2. Create Tenant
            $tenant = Tenant::create([
                'name'               => $input['business_name'],
                'slug'               => Str::slug($input['business_name']),
                'subdomain'          => Str::slug($input['business_name']),
                'plan_id'            => $plan->id,
                'status'             => 'active',
                'preferred_currency' => 'KES',
                'timezone'           => 'Africa/Nairobi',
                'country_code'       => 'KE',
            ]);

            // 3. Create User attached to Tenant
            $user = User::create([
                'tenant_id' => $tenant->id,
                'name'      => $input['name'],
                'email'     => $input['email'],
                'password'  => $input['password'],
            ]);

            // 4. Update tenant owner
            $tenant->update(['owner_user_id' => $user->id]);

            // 5. Initialize Tenant Defaults
            TenantSettings::create([
                'tenant_id'      => $tenant->id,
                'programme_name' => $tenant->name . ' Rewards',
                'points_name'    => 'Points',
            ]);

            LoyaltyProgram::create([
                'tenant_id' => $tenant->id,
                'name'      => 'Default Programme',
                'is_active' => true,
            ]);

            SmsWallet::create([
                'tenant_id'       => $tenant->id,
                'credits_balance' => 0,
            ]);

            // 6. Assign Merchant Owner Role
            $user->assignRole(RoleEnum::MerchantOwner->value);

            return $user;
        });
    }
}
