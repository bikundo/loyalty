<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\Campaign;
use App\Models\Customer;
use App\Models\Redemption;
use App\Models\PointTransaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /**
     * Get high-level summary stats for the dashboard.
     */
    public function getSummaryStats(Tenant $tenant, int $days = 30): array
    {
        $start = now()->subDays($days);

        return [
            'active_customers' => Customer::where('tenant_id', $tenant->id)
                ->where('last_visit_at', '>=', now()->subDays(90))
                ->count(),

            'points_liability' => (int) Customer::where('tenant_id', $tenant->id)
                ->sum('total_points'),

            'new_enrolments' => Customer::where('tenant_id', $tenant->id)
                ->where('created_at', '>=', $start)
                ->count(),

            'points_awarded' => (int) PointTransaction::where('tenant_id', $tenant->id)
                ->where('type', 'earn')
                ->where('created_at', '>=', $start)
                ->sum('points'),

            'points_redeemed' => (int) PointTransaction::where('tenant_id', $tenant->id)
                ->where('type', 'redeem')
                ->where('created_at', '>=', $start)
                ->sum('points'),

            'revenue_influenced' => (int) PointTransaction::where('tenant_id', $tenant->id)
                ->where('type', 'earn')
                ->where('created_at', '>=', $start)
                ->sum('amount_spent_kes'),
        ];
    }

    /**
     * Get enrollment trends for Flux charts.
     */
    public function getEnrollmentTrend(Tenant $tenant, int $days = 30): array
    {
        $stats = Customer::where('tenant_id', $tenant->id)
            ->where('created_at', '>=', now()->subDays($days))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->pluck('count', 'date');

        return $this->formatForFluxChart($this->fillEmptyDates($stats, $days));
    }

    /**
     * Get point circulation flow (Earn vs Redeem).
     */
    public function getPointFlowTrend(Tenant $tenant, int $days = 30): array
    {
        $earned = PointTransaction::where('tenant_id', $tenant->id)
            ->where('type', 'earn')
            ->where('created_at', '>=', now()->subDays($days))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('sum(points) as total'))
            ->groupBy('date')
            ->pluck('total', 'date');

        $redeemed = PointTransaction::where('tenant_id', $tenant->id)
            ->where('type', 'redeem')
            ->where('created_at', '>=', now()->subDays($days))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('sum(points) as total'))
            ->groupBy('date')
            ->pluck('total', 'date');

        return [
            'earned'   => $this->formatForFluxChart($this->fillEmptyDates($earned, $days)),
            'redeemed' => $this->formatForFluxChart($this->fillEmptyDates($redeemed, $days)),
        ];
    }

    /**
     * Get top 10 most popular rewards by redemption count.
     */
    public function getPopularRewards(Tenant $tenant, int $limit = 10): Collection
    {
        return Redemption::where('tenant_id', $tenant->id)
            ->with('reward')
            ->select('reward_id', DB::raw('count(*) as redemptions_count'), DB::raw('sum(points_used) as total_points'))
            ->groupBy('reward_id')
            ->orderByDesc('redemptions_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Get top 10 customers by points balance or lifetime activity.
     */
    public function getTopCustomers(Tenant $tenant, int $limit = 10): Collection
    {
        return Customer::where('tenant_id', $tenant->id)
            ->orderByDesc('total_points')
            ->limit($limit)
            ->get();
    }

    /**
     * Calculate Campaign Conversion ROI.
     * (% of recipients who visited/spent within 7 days post-campaign).
     */
    public function getCampaignROI(Campaign $campaign): array
    {
        $recepientIds = $campaign->recipients()->pluck('customer_id');

        $visitors = PointTransaction::whereIn('customer_id', $recepientIds)
            ->where('type', 'earn')
            ->whereBetween('created_at', [
                $campaign->dispatched_at,
                $campaign->dispatched_at->addDays(7),
            ])
            ->distinct('customer_id')
            ->count();

        $conversionRate = $campaign->recipients_total > 0
            ? ($visitors / $campaign->recipients_total) * 100
            : 0;

        return [
            'total_recipients'   => $campaign->recipients_total,
            'converted_visitors' => $visitors,
            'conversion_rate'    => round($conversionRate, 2),
        ];
    }

    /**
     * Helper to ensure dataset has no gaps for charts.
     */
    protected function fillEmptyDates(Collection $data, int $days): Collection
    {
        $result = collect();

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $result->put($date, (int) $data->get($date, 0));
        }

        return $result;
    }

    /**
     * Format collection for Flux <flux:chart> component using array shape.
     */
    protected function formatForFluxChart(Collection $data): array
    {
        return $data->map(fn ($value, $date) => [
            'date'  => $date,
            'value' => $value,
        ])->values()->toArray();
    }
}
