<?php

namespace App\Services\Esim\Contracts;

use App\Models\EsimOrder;
use App\Models\User;

interface EsimOrderServiceInterface
{
    public function purchase(User $user, string $packageCode, ?string $idempotencyKey = null): EsimOrder;

    public function findOwned(User $user, string $orderId): EsimOrder;
}
