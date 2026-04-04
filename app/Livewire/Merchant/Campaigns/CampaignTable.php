<?php

namespace App\Livewire\Merchant\Campaigns;

use App\Models\Tenant;
use Livewire\Component;
use App\Models\Campaign;
use Livewire\WithPagination;
use App\Services\TenantContext;

class CampaignTable extends Component
{
    use WithPagination;

    public function render(TenantContext $tenantContext)
    {
        /** @var Tenant $tenant */
        $tenant = $tenantContext->current();

        $campaigns = Campaign::where('tenant_id', $tenant->id)
            ->with('creator')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('livewire.merchant.campaigns.campaign-table', [
            'campaigns' => $campaigns,
        ]);
    }
}
