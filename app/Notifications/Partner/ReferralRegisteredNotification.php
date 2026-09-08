<?php

namespace App\Notifications\Partner;

use App\Models\PromoUsage;
use App\Models\User;
use App\Notifications\Concerns\UsesNotifiableChannels;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReferralRegisteredNotification extends Notification
{
    use UsesNotifiableChannels;

    public function __construct(
        public User $user,
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
            ->subject(__('notifications.referral.subject'))
            ->line(__('notifications.referral.body', [
                'name' => $this->user->name,
                'amount' => number_format((float) $this->usage->partner_reward, 2),
            ]));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'referral_registered',
            'title' => __('notifications.referral.title'),
            'body' => __('notifications.referral.body', [
                'name' => $this->user->name,
                'amount' => number_format((float) $this->usage->partner_reward, 2),
            ]),
            'user_id' => $this->user->id,
            'promo_usage_id' => $this->usage->id,
            'amount' => (string) $this->usage->partner_reward,
        ];
    }
}
