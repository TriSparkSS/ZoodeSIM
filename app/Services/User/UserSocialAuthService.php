<?php

namespace App\Services\User;

use App\Models\User;
use App\Services\Auth\AccountEmailUniqueness;
use App\Services\Auth\AuthActivityLogger;
use App\Services\Auth\Contracts\FirebaseTokenVerifierInterface;
use App\Services\User\Contracts\UserSocialAuthServiceInterface;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserSocialAuthService implements UserSocialAuthServiceInterface
{
    public function __construct(
        protected FirebaseTokenVerifierInterface $tokens,
        protected UserRegistrationService $registration,
        protected UserAuthService $auth,
        protected AuthActivityLogger $logger,
        protected AccountEmailUniqueness $emails,
    ) {}

    public function authenticate(
        string $idToken,
        string $provider,
        ?string $name = null,
        ?string $referralCode = null,
        ?string $deviceId = null,
        ?string $ip = null,
    ): array {
        $identity = $this->tokens->verify($idToken);

        if ($identity->provider() !== $provider) {
            throw ValidationException::withMessages([
                'provider' => __('api.user.social_provider_mismatch'),
            ]);
        }

        $existing = User::query()->withTrashed()->where('firebase_uid', $identity->uid)->first();

        if ($existing) {
            $this->rejectIfDeleted($existing);

            return $this->issueExisting($existing);
        }

        if ($identity->email !== null && $this->emails->isTakenByPartner($identity->email)) {
            throw ValidationException::withMessages([
                'email' => __('api.validation.email_unique'),
            ]);
        }

        if ($identity->email !== null) {
            $byEmail = $this->emails->findUser($identity->email);

            if ($byEmail) {
                $this->rejectIfDeleted($byEmail);

                if (filled($byEmail->firebase_uid) && $byEmail->firebase_uid !== $identity->uid) {
                    throw ValidationException::withMessages([
                        'email' => __('api.validation.email_unique'),
                    ]);
                }

                $payload = ['firebase_uid' => $identity->uid];

                if (blank($byEmail->password)) {
                    $payload['auth_provider'] = $provider;
                }

                if ($identity->emailVerified && $byEmail->email_verified_at === null) {
                    $payload['email_verified_at'] = now();
                }

                $byEmail->update($payload);

                return $this->issueExisting($byEmail->fresh() ?? $byEmail);
            }
        }

        if ($identity->email === null) {
            throw ValidationException::withMessages([
                'id_token' => __('api.user.social_email_missing'),
            ]);
        }

        $displayName = $this->resolvedName($identity->name, $name, $identity->email);

        $created = $this->registration->registerSocial(
            name: $displayName,
            email: $identity->email,
            firebaseUid: $identity->uid,
            authProvider: $provider,
            emailVerified: $identity->emailVerified,
            referralCode: $referralCode,
            deviceId: $deviceId,
            ip: $ip,
        );

        $token = $this->auth->issueToken($created['user']);
        $this->logger->loginSuccess(UserAuthService::GUARD, $created['user'], [
            'provider' => $provider,
            'new_user' => true,
        ]);

        return [
            'user' => $created['user'],
            'token' => $token,
            'is_new_user' => true,
            'bonus_mb' => $created['bonus_mb'],
            'bonus_type' => $created['bonus_type'],
            'bonus_amount' => $created['bonus_amount'],
        ];
    }

    /**
     * @return array{user: User, token: string, is_new_user: bool, bonus_mb: int, bonus_type: null, bonus_amount: int}
     */
    protected function issueExisting(User $user): array
    {
        $token = $this->auth->issueToken($user);
        $this->logger->loginSuccess(UserAuthService::GUARD, $user, [
            'provider' => $user->auth_provider,
            'new_user' => false,
        ]);

        return [
            'user' => $user,
            'token' => $token,
            'is_new_user' => false,
            'bonus_mb' => (int) $user->bonus_mb,
            'bonus_type' => null,
            'bonus_amount' => 0,
        ];
    }

    protected function rejectIfDeleted(User $user): void
    {
        if (! $user->trashed()) {
            return;
        }

        throw ValidationException::withMessages([
            'id_token' => __('api.user.account_deleted'),
        ]);
    }

    protected function resolvedName(?string $tokenName, ?string $requestName, string $email): string
    {
        foreach ([$requestName, $tokenName] as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        $local = Str::before($email, '@');

        return $local !== '' ? $local : 'User';
    }
}
