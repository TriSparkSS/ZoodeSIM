<?php

namespace App\DataTransferObjects;

readonly class ResellPortalOrder
{
    public function __construct(
        public string $id,
        public ?string $clientId,
        public ?string $packageCode,
        public ?string $packageName,
        public ?string $location,
        public ?string $amount,
        public ?string $status,
        public ?string $createdAt,
    ) {}
}
