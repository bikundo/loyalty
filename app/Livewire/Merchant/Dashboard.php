<?php

namespace App\Livewire\Merchant;

use Livewire\Component;
use App\Services\TenantContext;
use Livewire\Attributes\Layout;
use App\Models\PointTransaction;
use App\Services\AnalyticsService;

class Dashboard extends Component
{
    #[Layout('layouts.admin')]
    public function render(TenantContext $tenantContext, AnalyticsService $analytics)
    {
        $tenant = $tenantContext->current();

        return view('livewire.merchant.dashboard', [
            'tenantName' => $tenant?->name ?? 'Merchant',
            'stats'      => $analytics->getDashboardStats($tenant),
            'trends'     => [
                'enrolments' => $analytics->getEnrollmentTrend($tenant),
                'pointFlow'  => $analytics->getPointFlowTrend($tenant),
            ],
            'recentTransactions' => PointTransaction::where('tenant_id', $tenant->id)
                ->with('customer')
                ->latest()
                ->limit(5)
                ->get(),
        ]);
    }
}
