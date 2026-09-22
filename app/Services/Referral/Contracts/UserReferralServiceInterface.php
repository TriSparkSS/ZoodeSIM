<?php

namespace App\Services\Referral\Contracts;

use App\DataTransferObjects\PromoValidationResult;
use App\Models\User;
use App\Models\UserReferral;

interface UserReferralServiceInterface
{
    public function validate(string $code, ?string $email = null): PromoValidationResult;

    public function findReferrer(string $code): User;

    public function assertNotSelfReferral(User $referrer, ?string $email): void;

    public function redeem(User $invitee, User $referrer): UserReferral;

    /**
     * @return array<string, mixed>
     */
    public function summary(User $user): array;
}
