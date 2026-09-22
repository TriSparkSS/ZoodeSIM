<?php

namespace App\Notifications\User;

use App\Models\User;
use App\Models\UserReferral;
use App\Notifications\Concerns\UsesNotifiableChannels;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserReferralReferrerNotification extends Notification
{
    use UsesNotifiableChannels;

    public function __construct(
        public User $invitee,
        public UserReferral $referral,
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
            ->subject(__('notifications.user_referral_referrer.subject'))
            ->line($this->body());
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'user_referral_referrer',
            'title' => __('notifications.user_referral_referrer.title'),
            'body' => $this->body(),
            'user_referral_id' => $this->referral->id,
            'referred_id' => $this->invitee->id,
            'bonus_amount' => (float) $this->referral->referrer_amount,
        ];
    }

    protected function body(): string
    {
        return __('notifications.user_referral_referrer.body', [
            'name' => $this->invitee->name,
            'amount' => number_format((float) $this->referral->referrer_amount, 2, '.', ''),
        ]);
    }
}
