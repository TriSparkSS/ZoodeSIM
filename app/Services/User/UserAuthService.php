<?php

namespace App\Services\User;

use App\Models\AuthActivityLog;
use App\Models\User;
use App\Services\Auth\AuthActivityLogger;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserAuthService
{
    public const GUARD = 'api';

    public function __construct(
        protected AuthActivityLogger $logger,
    ) {}

    /**
     * @return array{user: User, token: string}
     *
     * @throws AuthenticationException
     * @throws ValidationException
     */
    public function login(string $email, string $password, int $maxAttempts = 5): array
    {
        $throttleKey = $this->throttleKey($email);

        $this->ensureIsNotRateLimited($email, $throttleKey, $maxAttempts);

        $user = User::query()->where('email', $email)->first();

        if ($user === null || ! Hash::check($password, $user->password)) {
            RateLimiter::hit($throttleKey);
            $this->logger->loginFailed(self::GUARD, $email);

            throw new AuthenticationException(__('auth.failed'));
        }

        RateLimiter::clear($throttleKey);

        $token = $this->issueToken($user);
        $this->logger->loginSuccess(self::GUARD, $user);

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function issueToken(User $user): string
    {
        return $user->createToken('user-api')->plainTextToken;
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
        $this->logger->logout(self::GUARD, $user);
    }

    public function throttleKey(string $email): string
    {
        return Str::transliterate(Str::lower($email).'|'.request()->ip().'|'.self::GUARD);
    }

    protected function ensureIsNotRateLimited(string $email, string $throttleKey, int $maxAttempts): void
    {
        if (! RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            return;
        }

        $seconds = RateLimiter::availableIn($throttleKey);

        $this->logger->log(self::GUARD, AuthActivityLog::EVENT_LOGIN_FAILED, emailAttempted: $email, meta: [
            'throttled' => true,
            'retry_after' => $seconds,
        ]);

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => (int) ceil($seconds / 60),
            ]),
        ])->status(429);
    }
}
