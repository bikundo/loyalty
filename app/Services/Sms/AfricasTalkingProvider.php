<?php

namespace App\Services\Sms;

use App\Models\Tenant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AfricasTalkingProvider implements SmsProviderInterface
{
    /**
     * Africa's Talking API endpoint for sandbox/production.
     */
    protected string $endpoint = 'https://api.africastalking.com/version1/messaging';

    /**
     * Send a single SMS message via Africa's Talking.
     *
     * @param string $to
     * @param string $message
     * @param Tenant $tenant
     * @return array{success: bool, messageId: string|null, error: string|null}
     */
    public function send(string $to, string $message, Tenant $tenant): array
    {
        $username = config('services.africastalking.username');
        $apiKey = config('services.africastalking.api_key');
        $from = config('services.africastalking.from', 'LoyaltyOS');

        if (empty($apiKey) || empty($username)) {
            return [
                'success' => false,
                'messageId' => null,
                'error' => 'Africa\'s Talking API configuration missing.',
            ];
        }

        try {
            $response = Http::asForm()
                ->withHeaders(['Accept' => 'application/json', 'apikey' => $apiKey])
                ->post($this->endpoint, [
                    'username' => $username,
                    'to' => $to,
                    'message' => $message,
                    'from' => $from,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $recipients = $data['SMSMessageData']['Recipients'] ?? [];
                
                if (count($recipients) > 0 && ($recipients[0]['status'] === 'Success' || $recipients[0]['status'] === 'Sent')) {
                    return [
                        'success' => true,
                        'messageId' => $recipients[0]['messageId'] ?? null,
                        'error' => null,
                    ];
                }

                return [
                    'success' => false,
                    'messageId' => null,
                    'error' => $recipients[0]['status'] ?? 'Unknown error from provider',
                ];
            }

            return [
                'success' => false,
                'messageId' => null,
                'error' => $response->reason() ?: 'Failed to connect to Africa\'s Talking.',
            ];

        } catch (\Exception $e) {
            Log::error("SMS Dispatch Error (AT): " . $e->getMessage());
            return [
                'success' => false,
                'messageId' => null,
                'error' => $e->getMessage(),
            ];
        }
    }
}
