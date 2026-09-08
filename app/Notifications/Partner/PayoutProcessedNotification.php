<?php

namespace App\Notifications\Partner;

use App\Models\Withdrawal;
use App\Notifications\Concerns\UsesNotifiableChannels;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PayoutProcessedNotification extends Notification
{
    use UsesNotifiableChannels;

    public function __construct(
        public Withdrawal $withdrawal,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return $this->channelsFor($notifiable);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('notifications.payout.subject'))
            ->line(__('notifications.payout.body', [
                'amount' => number_format((float) $this->withdrawal->amount, 2),
                'status' => $this->withdrawal->status,
            ]));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payout_processed',
            'title' => __('notifications.payout.title'),
            'body' => __('notifications.payout.body', [
                'amount' => number_format((float) $this->withdrawal->amount, 2),
                'status' => $this->withdrawal->status,
            ]),
            'withdrawal_id' => $this->withdrawal->id,
            'amount' => (string) $this->withdrawal->amount,
            'status' => $this->withdrawal->status,
        ];
    }
}
