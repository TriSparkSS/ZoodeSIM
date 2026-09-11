<?php

namespace App\Services\User;

use App\Models\PromoCode;
use App\Models\User;
use App\Services\Esim\Contracts\ResellPortalUserClientServiceInterface;
use App\Services\Fraud\Contracts\DeviceFraudServiceInterface;
use App\Services\Promo\PromoEligibilityService;
use App\Services\Promo\PromoRedemptionService;
use Illuminate\Support\Facades\DB;

class UserRegistrationService
{
    public function __construct(
        protected PromoEligibilityService $eligibility,
        protected PromoRedemptionService $redemption,
        protected DeviceFraudServiceInterface $fraud,
        protected ResellPortalUserClientServiceInterface $clients,
    ) {}

    /**
     * @return array{user: User, bonus_mb: int, bonus_type: string|null, bonus_amount: float|int}
     */
    public function register(
        string $name,
        string $email,
        string $phone,
        string $password,
        ?string $referralCode = null,
        ?string $deviceId = null,
        ?string $ip = null,
    ): array {
        $promo = $this->resolvePromo($referralCode, $email);
        $this->fraud->assertCanRegister($deviceId, $ip, $promo !== null);

        $result = DB::transaction(function () use ($name, $email, $phone, $password, $promo, $deviceId, $ip) {
            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'password' => $password,
                'device_id' => $deviceId,
                'registration_ip' => $ip,
            ]);

            if ($promo === null) {
                return [
                    'user' => $user,
                    'bonus_mb' => 0,
                    'bonus_type' => null,
                    'bonus_amount' => 0,
                ];
            }

            $usage = $this->redemption->redeem($user, $promo);
            $user->refresh();

            return [
                'user' => $user,
                ...$usage->apiBonusPayload(),
            ];
        });

        if ($this->clients->tryEnsure($result['user']) !== null) {
            $result['user']->refresh();
        }

        return $result;
    }

    protected function resolvePromo(?string $referralCode, string $email): ?PromoCode
    {
        if ($referralCode === null || trim($referralCode) === '') {
            return null;
        }

        return $this->eligibility->assertEligible($referralCode, $email);
    }
}
