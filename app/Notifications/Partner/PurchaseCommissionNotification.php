<?php

namespace App\Notifications\Partner;

use App\Models\EsimOrder;
use App\Models\User;
use App\Notifications\Concerns\UsesNotifiableChannels;
use App\Support\Money;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PurchaseCommissionNotification extends Notification
{
    use UsesNotifiableChannels;

    public function __construct(
        public User $user,
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
            ->subject(__('notifications.purchase.subject'))
            ->line(__('notifications.purchase.body', [
                'name' => $this->user->name,
                'amount' => $this->amount->format(),
            ]));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'purchase_commission',
            'title' => __('notifications.purchase.title'),
            'body' => __('notifications.purchase.body', [
                'name' => $this->user->name,
                'amount' => $this->amount->format(),
            ]),
            'user_id' => $this->user->id,
            'order_id' => $this->order->id,
            'amount' => $this->amount->toDecimal(),
        ];
    }
}
