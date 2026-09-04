<?php

namespace App\Services\Auth;

use App\Models\AuthSession;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AuthSessionManager
{
    public function __construct(
        protected AuthActivityLogger $activityLogger,
        protected ?Request $request = null,
    ) {
        $this->request ??= request();
    }

    public function start(Authenticatable $user, string $guard): AuthSession
    {
        $userAgent = $this->request?->userAgent();

        return AuthSession::query()->create([
            'authenticatable_type' => $user->getMorphClass(),
            'authenticatable_id' => $user->getAuthIdentifier(),
            'guard' => $guard,
            'session_id' => session()->getId(),
            'ip_address' => $this->request?->ip(),
            'user_agent' => $userAgent,
            'device_label' => $this->deviceLabel($userAgent),
            'login_at' => now(),
            'last_activity_at' => now(),
            'revoked_at' => null,
        ]);
    }

    public function touchCurrent(?string $sessionId = null): void
    {
        $sessionId ??= session()->getId();

        AuthSession::query()
            ->active()
            ->where('session_id', $sessionId)
            ->update(['last_activity_at' => now()]);
    }

    /**
     * @return Collection<int, AuthSession>
     */
    public function activeFor(Authenticatable $user): Collection
    {
        return AuthSession::query()
            ->whereMorphedTo('authenticatable', $user)
            ->active()
            ->orderByDesc('last_activity_at')
            ->get();
    }

    /**
     * @return Collection<int, AuthSession>
     */
    public function historyFor(Authenticatable $user, int $limit = 50): Collection
    {
        return AuthSession::query()
            ->whereMorphedTo('authenticatable', $user)
            ->orderByDesc('login_at')
            ->limit($limit)
            ->get();
    }

    public function findOwned(Authenticatable $user, int $sessionId): ?AuthSession
    {
        return AuthSession::query()
            ->whereMorphedTo('authenticatable', $user)
            ->whereKey($sessionId)
            ->first();
    }

    public function terminate(AuthSession $session, Authenticatable $actor, string $guard): void
    {
        if ($session->revoked_at !== null) {
            return;
        }

        $laravelSessionId = $session->session_id;

        $session->update([
            'revoked_at' => now(),
            'session_id' => null,
        ]);

        if ($laravelSessionId) {
            $this->destroyLaravelSession($laravelSessionId);
        }

        $this->cycleRememberToken($actor);

        $this->activityLogger->sessionTerminated($guard, $actor, [
            'auth_session_id' => $session->id,
            'was_current' => $laravelSessionId === session()->getId(),
            'device_label' => $session->device_label,
            'ip_address' => $session->ip_address,
        ]);
    }

    public function terminateOthers(Authenticatable $user, string $guard): void
    {
        $currentId = session()->getId();

        $sessions = AuthSession::query()
            ->whereMorphedTo('authenticatable', $user)
            ->active()
            ->where(function ($query) use ($currentId) {
                $query->whereNull('session_id')
                    ->orWhere('session_id', '!=', $currentId);
            })
            ->get();

        foreach ($sessions as $session) {
            $this->terminate($session, $user, $guard);
        }
    }

    /**
     * Revoke every active tracked session for the user (e.g. admin reset partner password).
     */
    public function terminateAll(Authenticatable $user, string $guard): void
    {
        $sessions = AuthSession::query()
            ->whereMorphedTo('authenticatable', $user)
            ->active()
            ->get();

        foreach ($sessions as $session) {
            $this->terminate($session, $user, $guard);
        }

        $this->cycleRememberToken($user);
    }

    public function revokeCurrent(Authenticatable $user): void
    {
        AuthSession::query()
            ->whereMorphedTo('authenticatable', $user)
            ->active()
            ->where('session_id', session()->getId())
            ->update([
                'revoked_at' => now(),
                'session_id' => null,
            ]);

        $this->cycleRememberToken($user);
    }

    protected function cycleRememberToken(Authenticatable $user): void
    {
        if (! method_exists($user, 'setRememberToken')) {
            return;
        }

        $user->setRememberToken(Str::random(60));

        if (method_exists($user, 'save')) {
            $user->save();
        }
    }

    protected function destroyLaravelSession(string $sessionId): void
    {
        if ($sessionId === session()->getId()) {
            return;
        }

        $driver = config('session.driver');

        if ($driver === 'database' && Schema::hasTable(config('session.table', 'sessions'))) {
            DB::table(config('session.table', 'sessions'))
                ->where('id', $sessionId)
                ->delete();

            return;
        }

        if ($driver === 'file') {
            $path = storage_path('framework/sessions/'.$sessionId);
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    protected function deviceLabel(?string $userAgent): string
    {
        if (! $userAgent) {
            return 'Unknown device';
        }

        $browser = match (true) {
            str_contains($userAgent, 'Edg/') => 'Edge',
            str_contains($userAgent, 'Chrome/') => 'Chrome',
            str_contains($userAgent, 'Firefox/') => 'Firefox',
            str_contains($userAgent, 'Safari/') && ! str_contains($userAgent, 'Chrome/') => 'Safari',
            default => 'Browser',
        };

        $os = match (true) {
            str_contains($userAgent, 'Windows') => 'Windows',
            str_contains($userAgent, 'Macintosh') => 'macOS',
            str_contains($userAgent, 'Android') => 'Android',
            str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad') => 'iOS',
            str_contains($userAgent, 'Linux') => 'Linux',
            default => 'Unknown OS',
        };

        return "{$browser} on {$os}";
    }
}
