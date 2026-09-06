<?php

namespace App\Http\Middleware;

use App\Services\Locale\LocaleManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetApiLocale
{
    public function __construct(
        protected LocaleManager $locales,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $requested = $request->query('lang');

        if (! is_string($requested) || ! $this->locales->isSupported($requested)) {
            $requested = $this->locales->fromAcceptLanguage($request->header('Accept-Language'));
        }

        $this->locales->applyWithoutSession($requested);

        return $next($request);
    }
}
