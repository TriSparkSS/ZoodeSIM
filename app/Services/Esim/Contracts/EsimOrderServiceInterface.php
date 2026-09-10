<?php

namespace App\Services\Esim\Contracts;

use App\Models\EsimOrder;
use App\Models\User;
use Illuminate\Support\Collection;

interface EsimOrderServiceInterface
{
    public function purchase(User $user, string $packageCode, ?string $idempotencyKey = null): EsimOrder;

    public function findOwned(User $user, string $orderId): EsimOrder;

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, EsimOrder>
     */
    public function listOwned(User $user, array $filters = []): Collection;
}
