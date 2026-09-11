<?php

namespace App\Notifications\User;

use App\Models\PromoCode;
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
            ->line($this->body());
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $bonus = $this->usage->apiBonusPayload();

        return [
            'type' => 'promo_bonus',
            'title' => __('notifications.bonus.title'),
            'body' => $this->body(),
            'promo_usage_id' => $this->usage->id,
            'bonus_type' => $bonus['bonus_type'],
            'bonus_amount' => $bonus['bonus_amount'],
            'bonus_mb' => $bonus['bonus_mb'],
        ];
    }

    protected function body(): string
    {
        if ($this->usage->bonus_type === PromoCode::BONUS_TYPE_USD) {
            return __('notifications.bonus.body_usd', [
                'bonus' => number_format((float) $this->usage->bonus_amount, 2, '.', ''),
            ]);
        }

        return __('notifications.bonus.body', [
            'bonus' => (int) $this->usage->bonus_mb_given,
        ]);
    }
}
