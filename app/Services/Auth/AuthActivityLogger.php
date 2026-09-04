<?php

namespace App\Services\Auth;

use App\Models\AuthActivityLog;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

class AuthActivityLogger
{
    public function __construct(
        protected ?Request $request = null,
    ) {
        $this->request ??= request();
    }

    public function log(
        string $guard,
        string $event,
        ?Authenticatable $subject = null,
        ?string $emailAttempted = null,
        array $meta = [],
    ): AuthActivityLog {
        return AuthActivityLog::query()->create([
            'authenticatable_type' => $subject?->getMorphClass(),
            'authenticatable_id' => $subject?->getAuthIdentifier(),
            'guard' => $guard,
            'event' => $event,
            'ip_address' => $this->request?->ip(),
            'user_agent' => $this->request?->userAgent(),
            'email_attempted' => $emailAttempted,
            'meta' => $meta === [] ? null : $meta,
            'created_at' => now(),
        ]);
    }

    public function loginSuccess(string $guard, Authenticatable $subject, array $meta = []): AuthActivityLog
    {
        return $this->log($guard, AuthActivityLog::EVENT_LOGIN_SUCCESS, $subject, meta: $meta);
    }

    public function loginFailed(string $guard, string $email, array $meta = []): AuthActivityLog
    {
        return $this->log($guard, AuthActivityLog::EVENT_LOGIN_FAILED, emailAttempted: $email, meta: $meta);
    }

    public function logout(string $guard, Authenticatable $subject, array $meta = []): AuthActivityLog
    {
        return $this->log($guard, AuthActivityLog::EVENT_LOGOUT, $subject, meta: $meta);
    }

    public function passwordChanged(string $guard, Authenticatable $subject, array $meta = []): AuthActivityLog
    {
        return $this->log($guard, AuthActivityLog::EVENT_PASSWORD_CHANGED, $subject, meta: $meta);
    }

    public function profileUpdated(string $guard, Authenticatable $subject, array $meta = []): AuthActivityLog
    {
        return $this->log($guard, AuthActivityLog::EVENT_PROFILE_UPDATED, $subject, meta: $meta);
    }

    public function sessionTerminated(string $guard, Authenticatable $subject, array $meta = []): AuthActivityLog
    {
        return $this->log($guard, AuthActivityLog::EVENT_SESSION_TERMINATED, $subject, meta: $meta);
    }
}
