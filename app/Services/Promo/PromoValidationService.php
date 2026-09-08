<?php

namespace App\Services\Promo;

use App\DataTransferObjects\PromoValidationResult;
use App\Services\Promo\Contracts\PromoAuditLoggerInterface;
use App\Services\Promo\Contracts\PromoValidationServiceInterface;
use Illuminate\Validation\ValidationException;

class PromoValidationService implements PromoValidationServiceInterface
{
    public function __construct(
        protected PromoEligibilityService $eligibility,
        protected PromoAuditLoggerInterface $audit,
    ) {}

    public function validate(string $code, ?string $email = null): PromoValidationResult
    {
        try {
            $promo = $this->eligibility->assertEligible($code, $email);
        } catch (ValidationException $e) {
            $reason = collect($e->errors())->flatten()->first();
            $message = is_string($reason) ? $reason : __('api.promo.invalid');
            $this->audit->validateFailed($code, $message, $email);

            return PromoValidationResult::invalid($message);
        }

        return PromoValidationResult::valid(
            (int) $promo->bonus_mb,
            (string) ($promo->partner?->name ?? ''),
        );
    }
}
