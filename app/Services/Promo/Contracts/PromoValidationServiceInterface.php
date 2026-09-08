<?php

namespace App\Services\Promo\Contracts;

use App\DataTransferObjects\PromoValidationResult;

interface PromoValidationServiceInterface
{
    public function validate(string $code, ?string $email = null): PromoValidationResult;
}
