<?php

use App\Models\PromoAuditLog;
use App\Models\User;
use App\Models\UserReferral;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        UserReferral::query()
            ->with([
                'referrer' => fn ($query) => $query->withTrashed(),
                'referred' => fn ($query) => $query->withTrashed(),
            ])
            ->orderBy('created_at')
            ->each(function (UserReferral $referral): void {
                $exists = PromoAuditLog::query()
                    ->where('action', PromoAuditLog::ACTION_REDEEMED)
                    ->where('meta->user_referral_id', $referral->id)
                    ->exists();

                if ($exists) {
                    return;
                }

                $referred = $referral->referred;
                $log = PromoAuditLog::query()->create([
                    'actor_type' => $referred?->getMorphClass() ?? (new User)->getMorphClass(),
                    'actor_id' => $referral->referred_id,
                    'action' => PromoAuditLog::ACTION_REDEEMED,
                    'code' => $referral->referrer?->referral_code,
                    'meta' => [
                        'user_referral_id' => $referral->id,
                        'referrer_id' => $referral->referrer_id,
                        'referrer_name' => $referral->referrer?->name,
                        'referred_id' => $referral->referred_id,
                        'referrer_amount' => (string) $referral->referrer_amount,
                        'referred_amount' => (string) $referral->referred_amount,
                    ],
                ]);

                PromoAuditLog::query()->whereKey($log->id)->update([
                    'created_at' => $referral->created_at,
                ]);
            });
    }

    public function down(): void
    {
        PromoAuditLog::query()
            ->where('action', PromoAuditLog::ACTION_REDEEMED)
            ->whereNull('promo_code_id')
            ->whereNotNull('meta->user_referral_id')
            ->delete();
    }
};
