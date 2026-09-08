<?php

namespace App\Services\ResellPortal;

use App\Services\ResellPortal\Contracts\ResellPortalClientInterface;

class ResellPortalConnectionService
{
    public function __construct(
        protected ResellPortalClientInterface $client,
    ) {}

    /**
     * Verify authenticated connectivity via GET /balance.
     * Internal/test use only — never expose this payload on User APIs.
     *
     * @return array<string, mixed>
     */
    public function verify(): array
    {
        return $this->client->get('balance');
    }
}
