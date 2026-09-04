<?php

namespace App\Http\Middleware;

use App\Services\Locale\LocaleManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function __construct(
        protected LocaleManager $locales,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $requested = $request->query('lang');

        if (! is_string($requested) || ! $this->locales->isSupported($requested)) {
            $requested = null;
        }

        if ($requested === null && ! session()->has('locale')) {
            $requested = $request->getPreferredLanguage($this->locales->codes());
        }

        $this->locales->applyCurrent($requested);

        return $next($request);
    }
}
