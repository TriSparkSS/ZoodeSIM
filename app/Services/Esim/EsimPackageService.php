<?php

namespace App\Services\Esim;

use App\DataTransferObjects\EsimPackageData;
use App\Exceptions\ResellPortalException;
use App\Services\Esim\Contracts\EsimPackageServiceInterface;
use App\Services\ResellPortal\Contracts\ResellPortalClientInterface;
use Illuminate\Support\Collection;

class EsimPackageService implements EsimPackageServiceInterface
{
    public function __construct(
        protected ResellPortalClientInterface $client,
    ) {}

    /**
     * @return Collection<int, EsimPackageData>
     */
    public function list(?string $country = null): Collection
    {
        $payload = $this->client->getEsimPackages($country);

        $packages = $payload['packages'] ?? null;

        if ($packages === null) {
            throw new ResellPortalException('invalid_response', 503, 'ResellPortal package payload is missing');
        }

        if (! is_array($packages)) {
            throw new ResellPortalException('invalid_response', 503, 'ResellPortal package payload is invalid');
        }

        return collect($packages)
            ->filter(fn (mixed $package): bool => is_array($package))
            ->map(fn (array $package): ?EsimPackageData => EsimPackageData::fromProvider($package))
            ->filter()
            ->values();
    }

    public function findByCode(string $packageCode): ?EsimPackageData
    {
        $normalized = strtoupper(trim($packageCode));

        return $this->list()->first(
            fn (EsimPackageData $package): bool => strtoupper($package->packageCode) === $normalized
        );
    }
}
