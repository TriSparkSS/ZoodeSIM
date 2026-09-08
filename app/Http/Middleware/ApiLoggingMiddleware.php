<?php

namespace App\Http\Middleware;

use App\Services\Logging\Contracts\ApiLoggerServiceInterface;
use App\Support\ApiLogContext;
use Closure;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ApiLoggingMiddleware
{
    public function __construct(
        protected ApiLoggerServiceInterface $logger,
        protected ApiLogContext $context,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $started = hrtime(true);

        try {
            $response = $next($request);
        } catch (Throwable $e) {
            report($e);

            $response = app(ExceptionHandler::class)->render($request, $e);

            $this->safeLog($request, $response, $started, $e);

            return $response;
        }

        $this->safeLog($request, $response, $started);

        return $response;
    }

    protected function safeLog(
        Request $request,
        ?Response $response,
        int $started,
        ?Throwable $exception = null,
    ): void {
        try {
            $user = $request->user();

            if ($user !== null && is_string($user->getAuthIdentifier())) {
                $this->context->forUser($user->getAuthIdentifier());
            }

            $this->logger->logInternalRequest($request, $response, $started, $exception);
        } catch (Throwable) {
            // Logging must never break the original API request.
        }
    }
}
