<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\Customer;
use App\Models\Redemption;
use App\Models\PointTransaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /**
     * Get high-level stats for the dashboard.
     *
     * @return array{enrolments: int, points_earned: int, redemptions: int}
     */
    public function getDashboardStats(Tenant $tenant): array
    {
        return [
            'enrolments' => Customer::where('tenant_id', $tenant->id)
                ->whereMonth('created_at', now()->month)
                ->count(),

            'points_earned' => (int) PointTransaction::where('tenant_id', $tenant->id)
                ->where('type', 'earn')
                ->whereMonth('created_at', now()->month)
                ->sum('points'),

            'redemptions' => Redemption::where('tenant_id', $tenant->id)
                ->whereMonth('created_at', now()->month)
                ->count(),
        ];
    }

    /**
     * Get enrollment trends for sparklines.
     */
    public function getEnrollmentTrend(Tenant $tenant, int $days = 7): array
    {
        $stats = Customer::where('tenant_id', $tenant->id)
            ->where('created_at', '>=', now()->subDays($days))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->pluck('count', 'date');

        return $this->fillEmptyDates($stats, $days);
    }

    /**
     * Get point circulation flow (Earn vs Redeem).
     */
    public function getPointFlowTrend(Tenant $tenant, int $days = 7): array
    {
        $earned = PointTransaction::where('tenant_id', $tenant->id)
            ->where('type', 'earn')
            ->where('created_at', '>=', now()->subDays($days))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('sum(points) as total'))
            ->groupBy('date')
            ->pluck('total', 'date');

        $redeemed = PointTransaction::where('tenant_id', $tenant->id)
            ->where('type', 'redemption')
            ->where('created_at', '>=', now()->subDays($days))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('sum(abs(points)) as total'))
            ->groupBy('date')
            ->pluck('total', 'date');

        return [
            'earned'   => $this->fillEmptyDates($earned, $days),
            'redeemed' => $this->fillEmptyDates($redeemed, $days),
        ];
    }

    /**
     * Helper to ensure dataset has no gaps for charts.
     */
    protected function fillEmptyDates(Collection $data, int $days): array
    {
        $result = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $result[$date] = $data->get($date, 0);
        }

        return $result;
    }
}
