<?php

namespace App\Services\Promo;

use App\Models\PromoCode;
use Illuminate\Support\Str;
use InvalidArgumentException;

class PromoCodeGenerator
{
    /**
     * Suggest a unique promo code from a partner/applicant name.
     */
    public function suggestFromName(string $name, int $suffixDigits = 2): string
    {
        $base = Str::upper(Str::substr(preg_replace('/[^A-Za-z0-9]/', '', explode(' ', trim($name))[0] ?? 'PARTNER'), 0, 8));

        if ($base === '') {
            $base = 'PARTNER';
        }

        for ($attempt = 0; $attempt < 25; $attempt++) {
            $min = 10 ** ($suffixDigits - 1);
            $max = (10 ** $suffixDigits) - 1;
            $candidate = $base.random_int($min, $max);

            if (! PromoCode::query()->where('code', $candidate)->exists()) {
                return $candidate;
            }
        }

        return $base.Str::upper(Str::random(4));
    }

    public function normalize(string $code): string
    {
        $normalized = Str::upper(preg_replace('/\s+/', '', trim($code)) ?? '');

        if ($normalized === '') {
            throw new InvalidArgumentException('Promo code cannot be empty.');
        }

        return $normalized;
    }
}
