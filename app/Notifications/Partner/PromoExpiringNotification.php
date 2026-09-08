<?php

namespace App\Notifications\Partner;

use App\Models\PromoCode;
use App\Notifications\Concerns\UsesNotifiableChannels;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PromoExpiringNotification extends Notification
{
    use UsesNotifiableChannels;

    public function __construct(
        public PromoCode $promo,
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
            ->subject(__('notifications.expiry.subject', ['code' => $this->promo->code]))
            ->line(__('notifications.expiry.body', [
                'code' => $this->promo->code,
                'date' => $this->promo->expires_at?->toDateString(),
            ]));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'promo_expiring',
            'title' => __('notifications.expiry.title'),
            'body' => __('notifications.expiry.body', [
                'code' => $this->promo->code,
                'date' => $this->promo->expires_at?->toDateString(),
            ]),
            'promo_code_id' => $this->promo->id,
            'code' => $this->promo->code,
        ];
    }
}
