<?php

namespace Database\Seeders;

use App\Enums\Role as RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use App\Enums\Permission as PermissionEnum;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Create all permissions for multiple guards
        $guards = ['web', 'api', 'cashier', 'customer'];

        foreach (PermissionEnum::cases() as $permission) {
            foreach ($guards as $guard) {
                Permission::findOrCreate($permission->value, $guard);
            }
        }

        // 2. Create Roles and Assign Permissions

        // SUPER ADMIN — all permissions on 'web' and 'api'
        $superAdmin = Role::findOrCreate(RoleEnum::SuperAdmin->value, 'web');
        $superAdmin->givePermissionTo(Permission::where('guard_name', 'web')->get());

        // MERCHANT OWNER — all tenant.* permissions
        $merchantOwner = Role::findOrCreate(RoleEnum::MerchantOwner->value, 'web');
        $merchantOwner->givePermissionTo(
            Permission::where('name', 'like', 'tenant.%')
                ->where('guard_name', 'web')
                ->get()
        );

        // MERCHANT MANAGER — most tenant.* except billing/owner tasks
        $merchantManager = Role::findOrCreate(RoleEnum::MerchantManager->value, 'web');
        $merchantManager->givePermissionTo([
            PermissionEnum::TenantSettingsManage->value,
            PermissionEnum::TenantStaffActivityView->value,
            PermissionEnum::TenantRulesManage->value,
            PermissionEnum::TenantRulesToggle->value,
            PermissionEnum::TenantRewardsManage->value,
            PermissionEnum::TenantCustomersView->value,
            PermissionEnum::TenantCustomersEnrol->value,
            PermissionEnum::TenantCustomersPointsView->value,
            PermissionEnum::TenantCustomersPointsAdjust->value,
            PermissionEnum::TenantCustomersHistoryView->value,
            PermissionEnum::TenantPointsAward->value,
            PermissionEnum::TenantPointsRedeemConfirm->value,
            PermissionEnum::TenantPointsVoid->value,
            PermissionEnum::TenantCampaignsManage->value,
            PermissionEnum::TenantCampaignsView->value,
            PermissionEnum::TenantSmsWalletView->value,
            PermissionEnum::TenantAnalyticsView->value,
            PermissionEnum::TenantReportsExport->value,
        ]);

        // CASHIER — specifically for the 'cashier' guard
        $cashierRole = Role::findOrCreate(RoleEnum::Cashier->value, 'cashier');
        $cashierRole->givePermissionTo([
            PermissionEnum::TenantCustomersView->value,
            PermissionEnum::TenantCustomersEnrol->value,
            PermissionEnum::TenantCustomersPointsView->value,
            PermissionEnum::TenantPointsAward->value,
            PermissionEnum::TenantPointsRedeemConfirm->value,
        ]);

        // CUSTOMER — specifically for the 'customer' guard
        $customerRole = Role::findOrCreate(RoleEnum::Customer->value, 'customer');
        $customerRole->givePermissionTo([
            PermissionEnum::CustomerBalanceView->value,
            PermissionEnum::CustomerHistoryView->value,
            PermissionEnum::CustomerRewardsView->value,
            PermissionEnum::CustomerRedemptionRequest->value,
        ]);

        // API CLIENT — for the 'api' guard
        $apiClient = Role::findOrCreate(RoleEnum::ApiClient->value, 'api');
        $apiClient->givePermissionTo([
            PermissionEnum::TenantCustomersView->value,
            PermissionEnum::TenantCustomersEnrol->value,
            PermissionEnum::TenantPointsAward->value,
        ]);
    }
}
