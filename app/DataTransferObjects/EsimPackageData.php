<?php

namespace App\DataTransferObjects;

readonly class EsimPackageData
{
    public function __construct(
        public string $packageCode,
        public string $name,
        public float $price,
        public string $location,
    ) {}

    /**
     * @param  array<string, mixed>  $package
     */
    public static function fromProvider(array $package): ?self
    {
        $code = trim((string) ($package['package_code'] ?? ''));

        if ($code === '') {
            return null;
        }

        return new self(
            packageCode: $code,
            name: (string) ($package['name'] ?? ''),
            price: (float) ($package['price'] ?? 0),
            location: (string) ($package['location'] ?? ''),
        );
    }

    /**
     * @return array{package_code: string, name: string, price: float, location: string}
     */
    public function toArray(): array
    {
        return [
            'package_code' => $this->packageCode,
            'name' => $this->name,
            'price' => $this->price,
            'location' => $this->location,
        ];
    }
}
