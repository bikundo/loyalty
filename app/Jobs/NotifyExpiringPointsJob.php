<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\PointTransaction;
use App\Models\Tenant;
use App\Services\Sms\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class NotifyExpiringPointsJob implements ShouldQueue
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
    public function handle(SmsService $smsService): void
    {
        $this->notifyForDays($smsService, 30);
        $this->notifyForDays($smsService, 7);
    }

    protected function notifyForDays(SmsService $smsService, int $days): void
    {
        // Find customers who have ANY points expiring on this exact day (approximate to date)
        PointTransaction::where('type', 'earn')
            ->where('points_remaining', '>', 0)
            ->whereDate('expires_at', now()->addDays($days)->toDateString())
            ->select('customer_id', 'tenant_id', DB::raw('SUM(points_remaining) as total_expiring'))
            ->groupBy('customer_id', 'tenant_id')
            ->chunkById(100, function ($groups) use ($smsService, $days) {
                foreach ($groups as $group) {
                    /** @var Customer $customer */
                    $customer = Customer::find($group->customer_id);
                    $tenant = Tenant::find($group->tenant_id);

                    if ($customer && $tenant) {
                        $message = "Heads up! You have {$group->total_expiring} points expiring in {$days} days at {$tenant->name}. Visit us soon to redeem them!";
                        $smsService->sendToCustomer($customer, $message, [
                            'trigger' => "expiry_warning_{$days}",
                        ]);
                    }
                }
            }, $column = 'customer_id');
    }
}
