<?php

namespace App\Services\Esim\Contracts;

use App\DataTransferObjects\EsimPackageData;
use Illuminate\Support\Collection;

interface EsimPackageServiceInterface
{
    /**
     * @return Collection<int, EsimPackageData>
     */
    public function list(?string $country = null): Collection;

    public function findByCode(string $packageCode): ?EsimPackageData;
}
