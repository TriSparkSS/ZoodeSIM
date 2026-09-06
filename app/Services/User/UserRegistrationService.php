<?php

namespace App\Services\User;

use App\Models\PromoCode;
use App\Models\User;
use App\Services\Promo\PromoEligibilityService;
use App\Services\Promo\PromoRedemptionService;
use Illuminate\Support\Facades\DB;

class UserRegistrationService
{
    public function __construct(
        protected PromoEligibilityService $eligibility,
        protected PromoRedemptionService $redemption,
    ) {}

    /**
     * @return array{user: User, bonus_mb: int}
     */
    public function register(
        string $name,
        string $email,
        string $phone,
        string $password,
        ?string $referralCode = null,
    ): array {
        $promo = $this->resolvePromo($referralCode, $email);

        return DB::transaction(function () use ($name, $email, $phone, $password, $promo) {
            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'password' => $password,
            ]);

            if ($promo === null) {
                return [
                    'user' => $user,
                    'bonus_mb' => 0,
                ];
            }

            $usage = $this->redemption->redeem($user, $promo);

            return [
                'user' => $user,
                'bonus_mb' => (int) $usage->bonus_mb_given,
            ];
        });
    }

    protected function resolvePromo(?string $referralCode, string $email): ?PromoCode
    {
        if ($referralCode === null || trim($referralCode) === '') {
            return null;
        }

        return $this->eligibility->assertEligible($referralCode, $email);
    }
}
