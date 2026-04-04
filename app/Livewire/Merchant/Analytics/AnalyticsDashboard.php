<?php

namespace App\Livewire\Merchant\Analytics;

use Livewire\Component;
use App\Services\TenantContext;
use App\Services\AnalyticsService;

class AnalyticsDashboard extends Component
{
    public int $days = 30;

    public function render(AnalyticsService $service, TenantContext $context)
    {
        $tenant = $context->current();

        return view('livewire.merchant.analytics.analytics-dashboard', [
            'stats'           => $service->getSummaryStats($tenant, $this->days),
            'enrollmentTrend' => $service->getEnrollmentTrend($tenant, $this->days),
            'pointFlow'       => $service->getPointFlowTrend($tenant, $this->days),
            'popularRewards'  => $service->getPopularRewards($tenant),
            'topCustomers'    => $service->getTopCustomers($tenant),
        ]);
    }
}
