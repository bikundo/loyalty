<?php

namespace App\Livewire\Merchant\Campaigns;

use Livewire\Component;
use App\Models\Tenant;
use App\Services\TenantContext;
use Livewire\Attributes\Layout;

class CampaignIndex extends Component
{
    #[Layout('layouts.admin')]
    public function render(TenantContext $tenantContext)
    {
        /** @var Tenant $tenant */
        $tenant = $tenantContext->current();

        return view('livewire.merchant.campaigns.campaign-index', [
            'tenant' => $tenant,
        ]);
    }
}
