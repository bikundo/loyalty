<?php

namespace App\Services\Sms;

use App\Models\Tenant;

interface SmsProviderInterface
{
    /**
     * Send a single SMS message.
     *
     * @param string $to
     * @param string $message
     * @param Tenant $tenant
     * @return array{success: bool, messageId: string|null, error: string|null}
     */
    public function send(string $to, string $message, Tenant $tenant): array;
}
