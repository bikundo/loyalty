<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\PointTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ExpireStalePointsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // 1. Find all expired points that still have remaining balance
        PointTransaction::where('type', 'earn')
            ->where('points_remaining', '>', 0)
            ->where('expires_at', '<', now())
            ->chunkById(100, function ($transactions) {
                foreach ($transactions as $transaction) {
                    $this->expirePoints($transaction);
                }
            });
    }

    protected function expirePoints(PointTransaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            /** @var Customer $customer */
            $customer = $transaction->customer()->lockForUpdate()->first();
            $pointsToExpire = $transaction->points_remaining;

            if ($pointsToExpire <= 0) {
                return;
            }

            // 1. Create the 'expire' transaction
            PointTransaction::create([
                'tenant_id'            => $transaction->tenant_id,
                'customer_id'          => $transaction->customer_id,
                'tenant_location_id'   => $transaction->tenant_location_id,
                'type'                 => 'expire',
                'points'               => $pointsToExpire,
                'points_remaining'     => -$pointsToExpire,
                'balance_after'        => $customer->total_points - $pointsToExpire,
                'note'                 => "Expired points from transaction #{$transaction->id}",
                'triggered_by'         => 'system',
                'idempotency_key'      => Str::uuid()->toString(),
                'created_at'           => now(),
            ]);

            // 2. Mark original transaction as fully depleted
            $transaction->update(['points_remaining' => 0]);

            // 3. Update customer balance
            $customer->decrement('total_points', $pointsToExpire);

            Log::info("Expired {$pointsToExpire} points for Customer {$customer->id} [Tenant {$transaction->tenant_id}]");
        });
    }
}
