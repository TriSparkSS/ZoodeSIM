<?php

namespace App\Services\User;

use App\Models\PromoCode;
use App\Models\User;
use App\Services\Esim\Contracts\ResellPortalUserClientServiceInterface;
use App\Services\Fraud\Contracts\DeviceFraudServiceInterface;
use App\Services\Promo\PromoCodeGenerator;
use App\Services\Promo\PromoEligibilityService;
use App\Services\Promo\PromoRedemptionService;
use App\Services\Referral\Contracts\UserReferralServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserRegistrationService
{
    public function __construct(
        protected PromoEligibilityService $eligibility,
        protected PromoRedemptionService $redemption,
        protected UserReferralServiceInterface $userReferrals,
        protected PromoCodeGenerator $codes,
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
        $resolved = $this->resolveReferral($referralCode, $email);
        $this->assertDeviceForResolved($resolved, $deviceId, $ip);

        $result = DB::transaction(function () use ($name, $email, $phone, $password, $resolved, $deviceId, $ip) {
            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'password' => $password,
                'auth_provider' => User::AUTH_PASSWORD,
                'device_id' => $deviceId,
                'registration_ip' => $ip,
            ]);

            return $this->applyResolvedReferral($user, $resolved);
        });

        if ($this->clients->tryEnsure($result['user']) !== null) {
            $result['user']->refresh();
        }

        return $result;
    }

    /**
     * @return array{user: User, bonus_mb: int, bonus_type: string|null, bonus_amount: float|int}
     */
    public function registerSocial(
        string $name,
        string $email,
        string $firebaseUid,
        string $authProvider,
        bool $emailVerified,
        ?string $referralCode = null,
        ?string $deviceId = null,
        ?string $ip = null,
    ): array {
        $resolved = $this->resolveReferral($referralCode, $email);
        $this->assertDeviceForResolved($resolved, $deviceId, $ip);

        $result = DB::transaction(function () use ($name, $email, $firebaseUid, $authProvider, $emailVerified, $resolved, $deviceId, $ip) {
            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'phone' => null,
                'password' => null,
                'firebase_uid' => $firebaseUid,
                'auth_provider' => $authProvider,
                'email_verified_at' => $emailVerified ? now() : null,
                'device_id' => $deviceId,
                'registration_ip' => $ip,
            ]);

            return $this->applyResolvedReferral($user, $resolved);
        });

        if ($this->clients->tryEnsure($result['user']) !== null) {
            $result['user']->refresh();
        }

        return $result;
    }

    /**
     * @return array{kind: 'promo'|'user'|null, promo?: PromoCode, referrer?: User}
     */
    protected function resolveReferral(?string $referralCode, string $email): array
    {
        if ($referralCode === null || trim($referralCode) === '') {
            return ['kind' => null];
        }

        try {
            $normalized = $this->codes->normalize($referralCode);
        } catch (\InvalidArgumentException) {
            throw ValidationException::withMessages([
                'referral_code' => __('api.promo.invalid'),
            ]);
        }

        if (PromoCode::query()->where('code', $normalized)->exists()) {
            return [
                'kind' => 'promo',
                'promo' => $this->eligibility->assertEligible($referralCode, $email),
            ];
        }

        $referrer = $this->userReferrals->findReferrer($referralCode);
        $this->userReferrals->assertNotSelfReferral($referrer, $email);

        return [
            'kind' => 'user',
            'referrer' => $referrer,
        ];
    }

    /**
     * @param  array{kind: 'promo'|'user'|null, promo?: PromoCode, referrer?: User}  $resolved
     */
    protected function assertDeviceForResolved(array $resolved, ?string $deviceId, ?string $ip): void
    {
        if ($resolved['kind'] === 'promo') {
            $this->fraud->assertCanRegister($deviceId, $ip, true);

            return;
        }

        if ($resolved['kind'] === 'user') {
            $this->fraud->assertCanRegisterUserReferral($deviceId, $ip);

            return;
        }

        $this->fraud->assertCanRegister($deviceId, $ip, false);
    }

    /**
     * @param  array{kind: 'promo'|'user'|null, promo?: PromoCode, referrer?: User}  $resolved
     * @return array{user: User, bonus_mb: int, bonus_type: string|null, bonus_amount: float|int}
     */
    protected function applyResolvedReferral(User $user, array $resolved): array
    {
        if ($resolved['kind'] === 'promo') {
            $usage = $this->redemption->redeem($user, $resolved['promo']);
            $user->refresh();

            return [
                'user' => $user,
                ...$usage->apiBonusPayload(),
            ];
        }

        if ($resolved['kind'] === 'user') {
            $record = $this->userReferrals->redeem($user, $resolved['referrer']);
            $user->refresh();

            return [
                'user' => $user,
                'bonus_mb' => 0,
                'bonus_type' => PromoCode::BONUS_TYPE_USD,
                'bonus_amount' => (float) $record->referred_amount,
            ];
        }

        return [
            'user' => $user,
            'bonus_mb' => 0,
            'bonus_type' => null,
            'bonus_amount' => 0,
        ];
    }
}
