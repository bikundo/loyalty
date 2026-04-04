<?php

namespace App\Services\Sms;

use App\Models\Tenant;

interface SmsProviderInterface
{
    /**
     * Send a single SMS message.
     *
     * @return array{success: bool, messageId: string|null, error: string|null}
     */
    public function send(string $to, string $message, Tenant $tenant): array;
}
