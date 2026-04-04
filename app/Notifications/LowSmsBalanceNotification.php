<?php

namespace App\Notifications;

use App\Models\SmsWallet;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class LowSmsBalanceNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public SmsWallet $wallet) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Action Required: Low SMS Credits')
            ->greeting("Hello {$notifiable->name},")
            ->line("Your SMS credit balance for {$this->wallet->tenant->name} is running low.")
            ->line("Current Balance: **{$this->wallet->credits_balance}** credits.")
            ->action('Top Up Credits', url('/admin/settings'))
            ->line('To ensure transactional messages continue to be delivered, please top up your balance soon.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tenant_id' => $this->wallet->tenant_id,
            'balance'   => $this->wallet->credits_balance,
            'message'   => "Low SMS credits: {$this->wallet->credits_balance} remaining.",
        ];
    }
}
