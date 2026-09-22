<?php

namespace App\Notifications\User;

use App\Models\UserReferral;
use App\Notifications\Concerns\UsesNotifiableChannels;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserReferralInviteeNotification extends Notification
{
    use UsesNotifiableChannels;

    public function __construct(
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
            ->subject(__('notifications.user_referral_invitee.subject'))
            ->line($this->body());
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'user_referral_invitee',
            'title' => __('notifications.user_referral_invitee.title'),
            'body' => $this->body(),
            'user_referral_id' => $this->referral->id,
            'bonus_type' => 'usd',
            'bonus_amount' => (float) $this->referral->referred_amount,
        ];
    }

    protected function body(): string
    {
        return __('notifications.user_referral_invitee.body', [
            'amount' => number_format((float) $this->referral->referred_amount, 2, '.', ''),
        ]);
    }
}
