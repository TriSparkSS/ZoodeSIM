<?php

namespace App\Services\Promo;

use App\Models\PromoCode;
use Illuminate\Validation\ValidationException;

class PromoEligibilityService
{
    public function __construct(
        protected PromoCodeService $promoCodes,
    ) {}

    public function assertEligible(string $code, ?string $registrantEmail = null): PromoCode
    {
        try {
            $normalized = $this->promoCodes->normalizeCode($code);
        } catch (\InvalidArgumentException) {
            throw $this->invalid();
        }

        $promo = PromoCode::query()
            ->with('partner')
            ->where('code', $normalized)
            ->first();

        if ($promo === null) {
            throw $this->invalid();
        }

        if (! $promo->is_active) {
            throw $this->failure('referral_code', __('api.promo.inactive'));
        }

        if ($promo->isExpired()) {
            throw $this->failure('referral_code', __('api.promo.expired'));
        }

        if ($promo->isExhausted()) {
            throw $this->failure('referral_code', __('api.promo.exhausted'));
        }

        $partner = $promo->partner;

        if ($partner === null || ! $partner->isActive()) {
            throw $this->failure('referral_code', __('api.promo.partner_inactive'));
        }

        if ($registrantEmail !== null && $registrantEmail !== '' && strcasecmp($registrantEmail, $partner->email) === 0) {
            throw $this->failure('referral_code', __('api.promo.self_referral'));
        }

        return $promo;
    }

    protected function invalid(): ValidationException
    {
        return $this->failure('referral_code', __('api.promo.invalid'));
    }

    protected function failure(string $field, string $message): ValidationException
    {
        return ValidationException::withMessages([
            $field => $message,
        ]);
    }
}
