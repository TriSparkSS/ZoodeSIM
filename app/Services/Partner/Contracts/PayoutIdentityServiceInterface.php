<?php

namespace App\Services\Partner\Contracts;

use App\Models\Partner;
use Illuminate\Validation\ValidationException;

interface PayoutIdentityServiceInterface
{
    /**
     * @throws ValidationException
     */
    public function assertReferralIsNotPartner(Partner $partner, ?string $payoutDetails = null): void;
}
