<?php

namespace App\Livewire\Merchant\Campaigns;

use App\Models\Tenant;
use Livewire\Component;
use App\Models\Campaign;
use Livewire\Attributes\On;
use Livewire\WithPagination;
use App\Services\TenantContext;
use App\Services\AnalyticsService;

class CampaignTable extends Component
{
    use WithPagination;

    public ?Campaign $selectedCampaign = null;

    #[On('campaign-created')]
    public function refresh()
    {
        $this->resetPage();
    }

    public function showInsights(Campaign $campaign)
    {
        $this->selectedCampaign = $campaign;
        $this->dispatch('modal-open', name: 'campaign-insights');
    }

    public function render(TenantContext $tenantContext, AnalyticsService $analyticsService)
    {
        /** @var Tenant $tenant */
        $tenant = $tenantContext->current();

        $campaigns = Campaign::where('tenant_id', $tenant->id)
            ->with(['creator', 'recipients'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $insights = $this->selectedCampaign
            ? $analyticsService->getCampaignROI($this->selectedCampaign)
            : null;

        return view('livewire.merchant.campaigns.campaign-table', [
            'campaigns' => $campaigns,
            'insights'  => $insights,
        ]);
    }
}
