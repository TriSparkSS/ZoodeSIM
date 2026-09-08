<?php

namespace App\Services\Promo\Contracts;

use App\Models\PromoAuditLog;
use App\Models\PromoCode;
use App\Models\PromoUsage;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

interface PromoAuditLoggerInterface
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
    ): PromoAuditLog;

    /**
     * @param  array<string, mixed>  $meta
     */
    public function created(PromoCode $promo, ?Model $actor = null, array $meta = []): PromoAuditLog;

    public function activated(PromoCode $promo, ?Model $actor = null): PromoAuditLog;

    public function deactivated(PromoCode $promo, ?Model $actor = null): PromoAuditLog;

    public function redeemed(PromoCode $promo, User $user, PromoUsage $usage): PromoAuditLog;

    public function validateFailed(string $code, string $reason, ?string $email = null): PromoAuditLog;
}
