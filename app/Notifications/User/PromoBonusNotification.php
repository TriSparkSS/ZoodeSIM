<?php

namespace App\Notifications\User;

use App\Models\PromoUsage;
use App\Notifications\Concerns\UsesNotifiableChannels;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PromoBonusNotification extends Notification
{
    use UsesNotifiableChannels;

    public function __construct(
        public PromoUsage $usage,
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
            ->subject(__('notifications.bonus.subject'))
            ->line(__('notifications.bonus.body', [
                'bonus' => (int) $this->usage->bonus_mb_given,
            ]));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'promo_bonus',
            'title' => __('notifications.bonus.title'),
            'body' => __('notifications.bonus.body', [
                'bonus' => (int) $this->usage->bonus_mb_given,
            ]),
            'promo_usage_id' => $this->usage->id,
            'bonus_mb' => (int) $this->usage->bonus_mb_given,
        ];
    }
}
