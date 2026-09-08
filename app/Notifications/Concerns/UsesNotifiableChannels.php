<?php

namespace App\Notifications\Concerns;

use App\Models\Partner;

trait UsesNotifiableChannels
{
    /**
     * @return list<string>
     */
    protected function channelsFor(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable instanceof Partner && ! $notifiable->email_notifications) {
            return $channels;
        }

        $channels[] = 'mail';

        return $channels;
    }
}
