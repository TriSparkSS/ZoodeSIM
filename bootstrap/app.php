<?php

use App\Http\Middleware\EnsureAdminAuthenticated;
use App\Http\Middleware\EnsurePartnerAuthenticated;
use App\Http\Middleware\SetApiLocale;
use App\Http\Middleware\SetLocale;
use App\Http\Responses\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SetLocale::class,
        ]);

        $middleware->api(prepend: [
            SetApiLocale::class,
        ]);

        $middleware->alias([
            'admin.auth' => EnsureAdminAuthenticated::class,
            'partner.auth' => EnsurePartnerAuthenticated::class,
        ]);

        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('admin') || $request->is('admin/*')) {
                return route('admin.login');
            }

            return route('login');
        });

        $middleware->redirectUsersTo(function (Request $request) {
            if (Auth::guard('admin')->check() || $request->is('admin/login')) {
                return route('admin.applications');
            }

            if (Auth::guard('partner')->check() || $request->is('login')) {
                return route('partner.dashboard');
            }

            return route('login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (ValidationException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $message = collect($e->errors())->flatten()->first() ?: __('api.validation_failed');

            return ApiResponse::error((string) $message, $e->errors(), $e->status);
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $message = $e->getMessage() !== '' && $e->getMessage() !== 'Unauthenticated.'
                ? $e->getMessage()
                : __('api.unauthenticated');

            return ApiResponse::error($message, [], 401);
        });

        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $seconds = (int) ($e->getHeaders()['Retry-After'] ?? 60);

            return ApiResponse::error(__('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => (int) ceil($seconds / 60),
            ]), [], 429);
        });
    })->create();
