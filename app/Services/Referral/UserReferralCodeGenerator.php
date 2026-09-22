<?php

namespace App\Services\Referral;

use App\Models\PromoCode;
use App\Models\User;
use App\Services\Promo\PromoCodeGenerator;
use InvalidArgumentException;

class UserReferralCodeGenerator
{
    public function __construct(
        protected PromoCodeGenerator $promoCodes,
    ) {}

    public function generate(): string
    {
        for ($attempt = 0; $attempt < 50; $attempt++) {
            $candidate = strtoupper(bin2hex(random_bytes(4)));

            if (! $this->exists($candidate)) {
                return $candidate;
            }
        }

        throw new InvalidArgumentException('Unable to generate a unique referral code.');
    }

    public function normalize(string $code): string
    {
        return $this->promoCodes->normalize($code);
    }

    public function exists(string $code): bool
    {
        $normalized = $this->normalize($code);

        return PromoCode::query()->where('code', $normalized)->exists()
            || User::query()->where('referral_code', $normalized)->exists();
    }
}
