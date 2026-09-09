<?php

namespace App\Support;

final class ResellPortalProviderId
{
    public static function from(mixed $value): ?string
    {
        if (is_int($value) && $value >= 1) {
            return (string) $value;
        }

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '' || $value === '0') {
            return null;
        }

        return $value;
    }
}
