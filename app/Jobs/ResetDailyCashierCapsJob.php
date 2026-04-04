<?php

namespace App\Jobs;

use App\Models\Cashier;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class ResetDailyCashierCapsJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        // Global reset for all cashiers across all tenants
        Cashier::query()->update(['total_awarded_today_kes' => 0]);
    }
}
