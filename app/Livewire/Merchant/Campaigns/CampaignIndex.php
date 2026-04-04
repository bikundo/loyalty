<?php

namespace App\Livewire\Merchant\Campaigns;

use App\Models\Tenant;
use Livewire\Component;
use App\Services\TenantContext;
use Livewire\Attributes\Layout;

class CampaignIndex extends Component
{
    public string $tab = 'manual';

    public function setTab(string $tab)
    {
        $this->tab = $tab;
    }

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
