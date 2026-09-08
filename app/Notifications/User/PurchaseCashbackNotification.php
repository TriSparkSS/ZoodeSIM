<?php

namespace App\Notifications\User;

use App\Models\EsimOrder;
use App\Notifications\Concerns\UsesNotifiableChannels;
use App\Support\Money;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PurchaseCashbackNotification extends Notification
{
    use UsesNotifiableChannels;

    public function __construct(
        public EsimOrder $order,
        public Money $amount,
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
            ->subject(__('notifications.cashback.subject'))
            ->line(__('notifications.cashback.body', [
                'amount' => $this->amount->format(),
            ]));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'purchase_cashback',
            'title' => __('notifications.cashback.title'),
            'body' => __('notifications.cashback.body', [
                'amount' => $this->amount->format(),
            ]),
            'order_id' => $this->order->id,
            'amount' => $this->amount->toDecimal(),
        ];
    }
}
