<?php

namespace App\Services\Auth;

use App\Models\Partner;
use App\Models\User;

class AccountEmailUniqueness
{
    public function normalize(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    public function findUser(string $email): ?User
    {
        $normalized = $this->normalize($email);

        if ($normalized === '') {
            return null;
        }

        return User::query()->withTrashed()->whereRaw('LOWER(email) = ?', [$normalized])->first();
    }

    public function isTaken(string $email, ?string $exceptUserId = null, ?string $exceptPartnerId = null): bool
    {
        $normalized = $this->normalize($email);

        if ($normalized === '') {
            return false;
        }

        $users = User::query()->withTrashed()->whereRaw('LOWER(email) = ?', [$normalized]);

        if ($exceptUserId !== null && $exceptUserId !== '') {
            $users->where('id', '!=', $exceptUserId);
        }

        if ($users->exists()) {
            return true;
        }

        $partners = Partner::query()->whereRaw('LOWER(email) = ?', [$normalized]);

        if ($exceptPartnerId !== null && $exceptPartnerId !== '') {
            $partners->where('id', '!=', $exceptPartnerId);
        }

        return $partners->exists();
    }

    public function isTakenByPartner(string $email): bool
    {
        $normalized = $this->normalize($email);

        if ($normalized === '') {
            return false;
        }

        return Partner::query()->whereRaw('LOWER(email) = ?', [$normalized])->exists();
    }
}
