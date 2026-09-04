<?php

namespace App\Services\Content;

use App\Models\ProgramSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProgramSettingService
{
    /**
     * @return Collection<int, ProgramSetting>
     */
    public function allOrdered(): Collection
    {
        return ProgramSetting::query()
            ->orderBy('sort_order')
            ->orderBy('key')
            ->get();
    }

    public function get(string $key, ?string $default = null): ?string
    {
        $value = ProgramSetting::query()->where('key', $key)->value('value');

        return $value !== null ? (string) $value : $default;
    }

    /**
     * @param  array<string, string>  $values  key => value
     */
    public function updateValues(array $values): void
    {
        DB::transaction(function () use ($values) {
            foreach ($values as $key => $value) {
                ProgramSetting::query()
                    ->where('key', $key)
                    ->update(['value' => $value]);
            }
        });
    }

    /**
     * Update label/description translations for the given locale.
     *
     * @param  array{label?: string, description?: string|null}  $attributes
     */
    public function updateTranslation(ProgramSetting $setting, string $locale, array $attributes): ProgramSetting
    {
        if (array_key_exists('label', $attributes) && $attributes['label'] !== null) {
            $setting->setTranslation('label', $locale, (string) $attributes['label']);
        }

        if (array_key_exists('description', $attributes)) {
            $setting->setTranslation('description', $locale, (string) ($attributes['description'] ?? ''));
        }

        $setting->save();

        return $setting->refresh();
    }
}
