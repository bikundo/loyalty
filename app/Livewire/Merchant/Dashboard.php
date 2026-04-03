<?php

namespace App\Livewire\Merchant;

use Livewire\Component;
use App\Models\Customer;
use App\Models\Redemption;
use App\Services\TenantContext;
use Livewire\Attributes\Layout;
use App\Models\PointTransaction;

class Dashboard extends Component
{
    #[Layout('layouts.admin')]
    public function render()
    {
        $tenant = app(TenantContext::class)->current();

        return view('livewire.merchant.dashboard', [
            'tenantName' => $tenant?->name ?? 'Merchant',
            'stats'      => [
                'customers'    => Customer::count(),
                'points_today' => PointTransaction::where('type', 'earn')
                    ->whereDate('created_at', today())
                    ->sum('points'),
                'redemptions_today' => Redemption::whereDate('created_at', today())->count(),
            ],
            'recentTransactions' => PointTransaction::with('customer')
                ->latest()
                ->limit(5)
                ->get(),
        ]);
    }
}
