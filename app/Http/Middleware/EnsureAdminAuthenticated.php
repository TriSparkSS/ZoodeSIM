<?php

namespace App\Http\Middleware;

use App\Services\Auth\AuthSessionManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminAuthenticated
{
    public function __construct(
        protected AuthSessionManager $sessions,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('admin')->check()) {
            if ($this->wantsJsonDenial($request)) {
                abort(401, 'Unauthenticated.');
            }

            // Partner sessions must not be steered into the admin login flow as a soft bypass UX.
            if (Auth::guard('partner')->check()) {
                abort(403, 'Unauthorized.');
            }

            return redirect()->guest(route('admin.login'));
        }

        // Dual-login isolation: an admin session must not keep a partner identity.
        if (Auth::guard('partner')->check()) {
            Auth::guard('partner')->logout();
        }

        $this->sessions->touchCurrent();

        return $next($request);
    }

    protected function wantsJsonDenial(Request $request): bool
    {
        return $request->expectsJson()
            || $request->is('api/*')
            || $request->is('api');
    }
}
