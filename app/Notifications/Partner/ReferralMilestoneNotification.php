<?php

namespace App\Notifications\Partner;

use App\Notifications\Concerns\UsesNotifiableChannels;
use App\Support\Money;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReferralMilestoneNotification extends Notification
{
    use UsesNotifiableChannels;

    public function __construct(
        public int $threshold,
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
            ->subject(__('notifications.milestone.subject', ['count' => $this->threshold]))
            ->line(__('notifications.milestone.body', [
                'count' => $this->threshold,
                'amount' => $this->amount->format(),
            ]));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'referral_milestone',
            'title' => __('notifications.milestone.title', ['count' => $this->threshold]),
            'body' => __('notifications.milestone.body', [
                'count' => $this->threshold,
                'amount' => $this->amount->format(),
            ]),
            'threshold' => $this->threshold,
            'amount' => $this->amount->toDecimal(),
        ];
    }
}
