<?php

namespace App\Services\Esim\Contracts;

use App\Models\User;

interface ResellPortalUserClientServiceInterface
{
    public function resolve(User $user): string;

    public function tryEnsure(User $user): ?string;
}
