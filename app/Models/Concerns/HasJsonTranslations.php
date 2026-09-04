<?php

namespace App\Models\Concerns;

use Spatie\Translatable\HasTranslations;

/**
 * Reusable Spatie JSON translations with project fallback locale.
 */
trait HasJsonTranslations
{
    use HasTranslations;

    public function getFallbackLocale(): string
    {
        return (string) config('locales.fallback', config('app.fallback_locale', 'en'));
    }

    /**
     * @return list<string>
     */
    public function translationLocales(): array
    {
        return array_keys(config('locales.supported', ['en' => []]));
    }

    /**
     * Set a translation for every supported locale (missing locales keep English).
     *
     * @param  array<string, string|null>  $translations
     */
    public function setTranslationsWithFallback(string $attribute, array $translations, ?string $fallbackText = null): static
    {
        $fallback = $fallbackText
            ?? $translations['en']
            ?? reset($translations)
            ?? '';

        foreach ($this->translationLocales() as $locale) {
            $value = $translations[$locale] ?? $fallback;
            $this->setTranslation($attribute, $locale, (string) $value);
        }

        return $this;
    }
}
