<?php

namespace App\Services\Locale;

use Carbon\Carbon;
use Illuminate\Support\Facades\App;

class LocaleManager
{
    public function supported(): array
    {
        return config('locales.supported', []);
    }

    /**
     * @return list<string>
     */
    public function codes(): array
    {
        return array_keys($this->supported());
    }

    public function default(): string
    {
        return (string) config('locales.default', 'en');
    }

    public function fallback(): string
    {
        return (string) config('locales.fallback', config('app.fallback_locale', 'en'));
    }

    public function current(): string
    {
        return App::getLocale();
    }

    public function isSupported(string $locale): bool
    {
        return in_array($locale, $this->codes(), true);
    }

    public function resolve(?string $requested = null): string
    {
        if ($requested !== null && $this->isSupported($requested)) {
            return $requested;
        }

        $sessionLocale = session('locale');

        if (is_string($sessionLocale) && $this->isSupported($sessionLocale)) {
            return $sessionLocale;
        }

        return $this->isSupported($this->default())
            ? $this->default()
            : $this->fallback();
    }

    public function set(string $locale): string
    {
        $resolved = $this->resolve($locale);

        session(['locale' => $resolved]);
        App::setLocale($resolved);

        if (class_exists(Carbon::class)) {
            Carbon::setLocale($resolved);
        }

        return $resolved;
    }

    public function applyCurrent(?string $requested = null): string
    {
        return $this->set($this->resolve($requested));
    }

    /**
     * Apply a locale for stateless API requests without writing the session.
     */
    public function applyWithoutSession(?string $requested = null): string
    {
        $resolved = ($requested !== null && $this->isSupported($requested))
            ? $requested
            : ($this->isSupported($this->default()) ? $this->default() : $this->fallback());

        App::setLocale($resolved);

        if (class_exists(Carbon::class)) {
            Carbon::setLocale($resolved);
        }

        return $resolved;
    }

    public function isRtl(?string $locale = null): bool
    {
        $locale ??= $this->current();

        return (bool) data_get($this->supported(), $locale.'.rtl', false);
    }

    /**
     * @return array{name: string, native: string, rtl: bool}|null
     */
    public function meta(string $locale): ?array
    {
        $meta = $this->supported()[$locale] ?? null;

        return is_array($meta) ? $meta : null;
    }

    /**
     * Resolve a locale from an Accept-Language header (API / JSON clients).
     */
    public function fromAcceptLanguage(?string $header): ?string
    {
        if ($header === null || trim($header) === '') {
            return null;
        }

        $parts = array_map('trim', explode(',', $header));

        foreach ($parts as $part) {
            $code = strtolower(substr(trim(explode(';', $part)[0]), 0, 2));

            if ($this->isSupported($code)) {
                return $code;
            }
        }

        return null;
    }
}
