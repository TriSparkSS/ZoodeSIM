<?php

namespace App\Services\ResellPortal\Contracts;

interface ResellPortalClientInterface
{
    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function get(string $path, array $query = []): array;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function post(string $path, array $payload = []): array;

    /**
     * @return array<string, mixed>
     */
    public function delete(string $path): array;

    /**
     * @return array<string, mixed>
     */
    public function getEsimPackages(?string $location = null): array;

    /**
     * @return array<string, mixed>
     */
    public function createClient(string $name, string $email, ?string $phone = null): array;

    /**
     * @return array<string, mixed>
     */
    public function createEsimOrder(int $clientId, string $packageCode): array;

    /**
     * @return array<string, mixed>
     */
    public function getBalance(): array;
}
