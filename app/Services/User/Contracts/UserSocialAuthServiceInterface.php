<?php

namespace App\Services\User\Contracts;

use App\Models\User;

interface UserSocialAuthServiceInterface
{
    /**
     * @return array{
     *     user: User,
     *     token: string,
     *     is_new_user: bool,
     *     bonus_mb: int,
     *     bonus_type: string|null,
     *     bonus_amount: float|int
     * }
     */
    public function authenticate(
        string $idToken,
        string $provider,
        ?string $name = null,
        ?string $referralCode = null,
        ?string $deviceId = null,
        ?string $ip = null,
    ): array;
}
