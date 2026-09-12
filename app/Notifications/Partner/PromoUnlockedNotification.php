<?php

namespace App\Notifications\Partner;

use App\Models\PromoCode;
use App\Notifications\Concerns\UsesNotifiableChannels;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PromoUnlockedNotification extends Notification
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
            ->subject(__('notifications.unlock.subject', ['code' => $this->promo->code]))
            ->line(__('notifications.unlock.body', [
                'code' => $this->promo->code,
                'count' => (int) $this->promo->unlock_requirement,
            ]));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'promo_unlocked',
            'title' => __('notifications.unlock.title'),
            'body' => __('notifications.unlock.body', [
                'code' => $this->promo->code,
                'count' => (int) $this->promo->unlock_requirement,
            ]),
            'promo_code_id' => $this->promo->id,
            'code' => $this->promo->code,
        ];
    }
}
