<?php

namespace App\Services\Promo;

use App\Models\Partner;
use App\Models\PromoCode;
use App\Models\PromoUsage;
use App\Services\Notifications\Contracts\NotificationDispatcherInterface;
use App\Services\Promo\Contracts\PromoAuditLoggerInterface;

class PromoLadderService
{
    public function __construct(
        protected PromoAuditLoggerInterface $audit,
        protected NotificationDispatcherInterface $notifications,
    ) {}

    public function referredUserCount(string $partnerId): int
    {
        return PromoUsage::query()->where('partner_id', $partnerId)->count();
    }

    public function sync(Partner|string $partner): void
    {
        $partnerId = $partner instanceof Partner ? $partner->id : $partner;
        $total = $this->referredUserCount($partnerId);

        $locked = PromoCode::query()
            ->where('partner_id', $partnerId)
            ->whereNotNull('unlock_requirement')
            ->whereNull('unlocked_at')
            ->orderBy('unlock_requirement')
            ->get();

        foreach ($locked as $promo) {
            if ($total < (int) $promo->unlock_requirement) {
                break;
            }

            $this->unlock($promo);
        }
    }

    protected function unlock(PromoCode $promo): void
    {
        $active = PromoCode::query()
            ->where('partner_id', $promo->partner_id)
            ->where('is_active', true)
            ->where('id', '!=', $promo->id)
            ->get();

        foreach ($active as $current) {
            $current->update(['is_active' => false]);
            $this->audit->deactivated($current->fresh() ?? $current);
        }

        $promo->update([
            'unlocked_at' => now(),
            'is_active' => true,
        ]);

        $fresh = $promo->fresh() ?? $promo;
        $this->audit->activated($fresh);

        $partner = $fresh->partner ?? Partner::query()->whereKey($fresh->partner_id)->first();

        if ($partner !== null) {
            $this->notifications->promoUnlocked($partner, $fresh);
        }
    }
}
