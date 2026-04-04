<?php

namespace App\Livewire\Merchant\Campaigns;

use App\Models\Tenant;
use App\Services\Sms\CampaignService;
use App\Services\TenantContext;
use Livewire\Component;
use Livewire\Attributes\Validate;
use Flux\Flux;

use App\Jobs\ProcessCampaignJob;

class CreateCampaignForm extends Component
{
    #[Validate('required|string|max:100')]
    public string $name = '';

    #[Validate('required|string|max:160')]
    public string $message = '';

    #[Validate('required|string|in:all,active_30,churning')]
    public string $segment_type = 'all';

    public function save(CampaignService $campaignService, TenantContext $tenantContext)
    {
        $this->validate();

        /** @var Tenant $tenant */
        $tenant = $tenantContext->current();

        try {
            $campaign = $campaignService->create($tenant, [
                'name' => $this->name,
                'message' => $this->message,
                'segment_type' => $this->segment_type,
            ]);

            if ($campaign->status === 'insufficient_funds') {
                Flux::toast(
                    text: "Campaign created but paused due to insufficient SMS credits. Please top up.", 
                    variant: 'warning'
                );
            } else {
                Flux::toast(
                    text: "Campaign '{$campaign->name}' created successfully with {$campaign->recipients_total} recipients.",
                    variant: 'success'
                );
                
                ProcessCampaignJob::dispatch($campaign);
            }

            $this->reset(['name', 'message', 'segment_type']);
            $this->dispatch('close-modal', name: 'create-campaign-modal');
            $this->dispatch('campaign-created'); // Refresh table

        } catch (\Exception $e) {
            Flux::toast(
                text: "Failed to create campaign: " . $e->getMessage(),
                variant: 'danger'
            );
        }
    }

    public function render()
    {
        return view('livewire.merchant.campaigns.create-campaign-form');
    }
}
