<?php

namespace App\Livewire\Merchant\Campaigns;

use Exception;
use Flux\Flux;
use App\Models\Tenant;
use Livewire\Component;
use App\Services\TenantContext;
use App\Services\CampaignService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;

class CreateCampaignForm extends Component
{
    #[Validate('required|string|max:100')]
    public string $name = '';

    #[Validate('required|string|max:160')]
    public string $message = '';

    #[Validate('required|string|in:all,active,lapsed,high_value')]
    public string $segment_type = 'all';

    public int $audienceCount = 0;

    public function mount(CampaignService $campaignService, TenantContext $tenantContext)
    {
        $this->updateAudienceCount($campaignService, $tenantContext);
    }

    public function updatedSegmentType(CampaignService $campaignService, TenantContext $tenantContext)
    {
        $this->updateAudienceCount($campaignService, $tenantContext);
    }

    protected function updateAudienceCount(CampaignService $campaignService, TenantContext $tenantContext)
    {
        /** @var Tenant $tenant */
        $tenant = $tenantContext->current();

        $this->audienceCount = $campaignService->getAudienceCount($tenant, [
            'type' => $this->segment_type,
        ]);
    }

    #[Computed]
    public function creditsRequired(): int
    {
        return $this->audienceCount * (int) ceil(strlen($this->message) / 160);
    }

    public function save(CampaignService $campaignService, TenantContext $tenantContext)
    {
        $this->validate();

        /** @var Tenant $tenant */
        $tenant = $tenantContext->current();

        try {
            $result = $campaignService->createAndDispatch(
                tenant: $tenant,
                createdByUserId: auth()->id(),
                name: $this->name,
                message: $this->message,
                segmentConfig: ['type' => $this->segment_type],
            );

            if (!$result['success']) {
                Flux::toast(
                    text: $result['error'],
                    variant: 'danger'
                );

                return;
            }

            $campaign = $result['campaign'];

            Flux::toast(
                text: "Campaign '{$campaign->name}' queued successfully for {$campaign->recipients_total} recipients.",
                variant: 'success'
            );

            $this->reset(['name', 'message', 'segment_type']);
            $this->updateAudienceCount($campaignService, $tenantContext);
            $this->dispatch('close-modal', name: 'create-campaign-modal');
            $this->dispatch('campaign-created'); // Refresh table

        }
        catch (Exception $e) {
            Flux::toast(
                text: 'Failed to create campaign: ' . $e->getMessage(),
                variant: 'danger'
            );
        }
    }

    public function render()
    {
        return view('livewire.merchant.campaigns.create-campaign-form');
    }
}
