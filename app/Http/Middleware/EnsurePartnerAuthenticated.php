<?php

namespace App\Http\Middleware;

use App\Models\Partner;
use App\Services\Auth\AuthSessionManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePartnerAuthenticated
{
    public function __construct(
        protected AuthSessionManager $sessions,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('partner')->check()) {
            if ($this->wantsJsonDenial($request)) {
                abort(401, 'Unauthenticated.');
            }

            // Admins must not use the partner panel via URL without a partner identity.
            if (Auth::guard('admin')->check()) {
                abort(403, 'Unauthorized.');
            }

            return redirect()->guest(route('login'));
        }

        /** @var Partner $partner */
        $partner = Auth::guard('partner')->user();

        if ($partner->isPending()) {
            Auth::guard('partner')->logout();

            if ($this->wantsJsonDenial($request)) {
                abort(403, 'Unauthorized.');
            }

            return redirect()
                ->route('login')
                ->withErrors(['email' => __('auth.partner.pending_approval')]);
        }

        if (! $partner->isActive()) {
            Auth::guard('partner')->logout();

            if ($this->wantsJsonDenial($request)) {
                abort(403, 'Unauthorized.');
            }

            return redirect()
                ->route('login')
                ->withErrors(['email' => __('auth.failed')]);
        }

        // Dual-login isolation: partner session must not keep an admin identity.
        if (Auth::guard('admin')->check()) {
            Auth::guard('admin')->logout();
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
