<?php

namespace App\Console\Commands;

use App\Models\PromoCode;
use App\Services\Notifications\Contracts\NotificationDispatcherInterface;
use Illuminate\Console\Command;

class NotifyExpiringPromoCodes extends Command
{
    protected $signature = 'promos:notify-expiring {--days=3}';

    protected $description = 'Notify partners when a promo code expires in the given number of days';

    public function handle(NotificationDispatcherInterface $notifications): int
    {
        $days = max(1, (int) $this->option('days'));
        $start = now()->startOfDay()->addDays($days);
        $end = now()->endOfDay()->addDays($days);

        $promos = PromoCode::query()
            ->with('partner')
            ->where('is_active', true)
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [$start, $end])
            ->get();

        $sent = 0;

        foreach ($promos as $promo) {
            $partner = $promo->partner;

            if ($partner === null || ! $partner->isActive()) {
                continue;
            }

            $notifications->promoExpiring($partner, $promo);
            $sent++;
        }

        $this->info("Sent {$sent} expiry notification(s).");

        return self::SUCCESS;
    }
}
