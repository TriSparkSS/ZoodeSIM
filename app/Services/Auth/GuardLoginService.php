<?php

namespace App\Services\Auth;

use App\Models\AuthActivityLog;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GuardLoginService
{
    public function __construct(
        protected AuthActivityLogger $logger,
        protected AuthSessionManager $sessions,
    ) {}

    /**
     * Authenticate against a dedicated guard with rate limiting and session tracking.
     *
     * @param  array{email: string, password: string}  $credentials
     * @param  list<string>  $logoutOtherGuards  Other guards to clear so panels stay isolated
     * @param  callable(Authenticatable): void|null  $afterAuthenticate  Extra checks (e.g. active status)
     *
     * @throws ValidationException
     */
    public function attempt(
        string $guard,
        array $credentials,
        bool $remember = false,
        array $logoutOtherGuards = [],
        ?callable $afterAuthenticate = null,
        int $maxAttempts = 5,
    ): Authenticatable {
        $email = (string) ($credentials['email'] ?? '');
        $throttleKey = $this->throttleKey($guard, $email);

        $this->ensureIsNotRateLimited($guard, $email, $throttleKey, $maxAttempts);

        if (! Auth::guard($guard)->attempt($credentials, $remember)) {
            RateLimiter::hit($throttleKey);
            $this->logger->loginFailed($guard, $email);

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        /** @var Authenticatable $user */
        $user = Auth::guard($guard)->user();

        try {
            if ($afterAuthenticate) {
                $afterAuthenticate($user);
            }
        } catch (\Throwable $e) {
            Auth::guard($guard)->logout();
            RateLimiter::hit($throttleKey);

            throw $e;
        }

        foreach ($logoutOtherGuards as $otherGuard) {
            if ($otherGuard !== $guard && Auth::guard($otherGuard)->check()) {
                Auth::guard($otherGuard)->logout();
            }
        }

        RateLimiter::clear($throttleKey);
        session()->regenerate();

        $this->sessions->start($user, $guard);
        $this->logger->loginSuccess($guard, $user);

        return $user;
    }

    protected function ensureIsNotRateLimited(string $guard, string $email, string $throttleKey, int $maxAttempts): void
    {
        if (! RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            return;
        }

        $seconds = RateLimiter::availableIn($throttleKey);

        $this->logger->log($guard, AuthActivityLog::EVENT_LOGIN_FAILED, emailAttempted: $email, meta: [
            'throttled' => true,
            'retry_after' => $seconds,
        ]);

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => (int) ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(string $guard, string $email): string
    {
        return Str::transliterate(Str::lower($email).'|'.request()->ip().'|'.$guard);
    }
}
