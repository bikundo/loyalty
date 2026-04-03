<?php

namespace App\Enums;

enum Permission: string
{
    // Platform administration — super_admin only
    case PlatformTenantsManage = 'platform.tenants.manage';
    case PlatformPlansManage = 'platform.plans.manage';
    case PlatformApiEnable = 'platform.api.enable';
    case PlatformAnalyticsView = 'platform.analytics.view';
    case PlatformAdminsManage = 'platform.admins.manage';
    case PlatformSmsLogsView = 'platform.sms_logs.view';
    case PlatformHorizonView = 'platform.horizon.view';

    // Merchant billing — merchant_owner only
    case TenantBillingManage = 'tenant.billing.manage';
    case TenantSmsWalletTopup = 'tenant.sms_wallet.topup';
    case TenantSettingsManage = 'tenant.settings.manage';
    case TenantApiKeysManage = 'tenant.api_keys.manage';

    // Staff management — merchant_owner only
    case TenantStaffInvite = 'tenant.staff.invite';
    case TenantStaffRolesAssign = 'tenant.staff.roles.assign';
    case TenantStaffPinsManage = 'tenant.staff.pins.manage';
    case TenantStaffActivityView = 'tenant.staff.activity.view';

    // Loyalty programme configuration
    case TenantRulesManage = 'tenant.rules.manage';
    case TenantRulesToggle = 'tenant.rules.toggle';
    case TenantRewardsManage = 'tenant.rewards.manage';

    // Customer management
    case TenantCustomersView = 'tenant.customers.view';
    case TenantCustomersEnrol = 'tenant.customers.enrol';
    case TenantCustomersPointsView = 'tenant.customers.points.view';
    case TenantCustomersPointsAdjust = 'tenant.customers.points.adjust';
    case TenantCustomersHistoryView = 'tenant.customers.history.view';

    // Point transactions
    case TenantPointsAward = 'tenant.points.award';
    case TenantPointsRedeemConfirm = 'tenant.points.redeem.confirm';
    case TenantPointsVoid = 'tenant.points.void';

    // SMS campaigns
    case TenantCampaignsManage = 'tenant.campaigns.manage';
    case TenantCampaignsView = 'tenant.campaigns.view';
    case TenantSmsWalletView = 'tenant.sms_wallet.view';

    // Analytics
    case TenantAnalyticsView = 'tenant.analytics.view';
    case TenantReportsExport = 'tenant.reports.export';

    // Customer self-service — customer role only
    case CustomerBalanceView = 'customer.balance.view';
    case CustomerHistoryView = 'customer.history.view';
    case CustomerRewardsView = 'customer.rewards.view';
    case CustomerRedemptionRequest = 'customer.redemption.request';
}
