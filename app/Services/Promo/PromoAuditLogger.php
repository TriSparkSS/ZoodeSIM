<?php

namespace App\Services\Promo;

use App\Models\PromoAuditLog;
use App\Models\PromoCode;
use App\Models\PromoUsage;
use App\Models\User;
use App\Services\Promo\Contracts\PromoAuditLoggerInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class PromoAuditLogger implements PromoAuditLoggerInterface
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function log(
        string $action,
        ?PromoCode $promo = null,
        ?Model $actor = null,
        ?string $code = null,
        array $meta = [],
    ): PromoAuditLog {
        $actor ??= $this->currentActor();
        $request = request();

        return PromoAuditLog::query()->create([
            'promo_code_id' => $promo?->id,
            'partner_id' => $promo?->partner_id,
            'actor_type' => $actor?->getMorphClass(),
            'actor_id' => $actor?->getKey(),
            'action' => $action,
            'code' => $code ?? $promo?->code,
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 512) ?: null,
            'meta' => $meta === [] ? null : $meta,
        ]);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function created(PromoCode $promo, ?Model $actor = null, array $meta = []): PromoAuditLog
    {
        return $this->log(PromoAuditLog::ACTION_CREATED, $promo, $actor, meta: $meta);
    }

    public function activated(PromoCode $promo, ?Model $actor = null): PromoAuditLog
    {
        return $this->log(PromoAuditLog::ACTION_ACTIVATED, $promo, $actor);
    }

    public function deactivated(PromoCode $promo, ?Model $actor = null): PromoAuditLog
    {
        return $this->log(PromoAuditLog::ACTION_DEACTIVATED, $promo, $actor);
    }

    public function redeemed(PromoCode $promo, User $user, PromoUsage $usage): PromoAuditLog
    {
        return $this->log(PromoAuditLog::ACTION_REDEEMED, $promo, $user, meta: [
            'user_id' => $user->id,
            'promo_usage_id' => $usage->id,
            'bonus_mb' => $usage->bonus_mb_given,
            'partner_reward' => (string) $usage->partner_reward,
            'device_id' => $usage->device_id,
        ]);
    }

    public function validateFailed(string $code, string $reason, ?string $email = null): PromoAuditLog
    {
        return $this->log(PromoAuditLog::ACTION_VALIDATE_FAILED, code: $code, meta: [
            'reason' => $reason,
            'email' => $email,
        ]);
    }

    protected function currentActor(): ?Model
    {
        return Auth::guard('admin')->user()
            ?? Auth::guard('partner')->user()
            ?? Auth::guard('sanctum')->user();
    }
}
