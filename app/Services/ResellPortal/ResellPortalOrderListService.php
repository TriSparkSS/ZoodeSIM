<?php

namespace App\Services\ResellPortal;

use App\DataTransferObjects\ResellPortalOrder;
use App\DataTransferObjects\ResellPortalOrderList;
use App\Exceptions\ResellPortalException;
use App\Services\ResellPortal\Contracts\ResellPortalClientInterface;

class ResellPortalOrderListService
{
    public function __construct(
        protected ResellPortalClientInterface $client,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = []): ResellPortalOrderList
    {
        try {
            $payload = $this->client->getEsimOrders($filters);
        } catch (ResellPortalException) {
            return new ResellPortalOrderList(available: false, rows: []);
        }

        $raw = $this->extractRows($payload);

        if ($raw === null) {
            return new ResellPortalOrderList(available: false, rows: []);
        }

        $rows = [];

        foreach ($raw as $row) {
            if (! is_array($row)) {
                continue;
            }

            $mapped = $this->mapRow($row);

            if ($mapped !== null) {
                $rows[] = $mapped;
            }
        }

        return new ResellPortalOrderList(available: true, rows: $rows);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<mixed>|null
     */
    protected function extractRows(array $payload): ?array
    {
        $orders = $payload['orders'] ?? null;

        if (is_array($orders)) {
            return array_is_list($orders) ? $orders : array_values($orders);
        }

        $nested = $payload['data']['orders'] ?? null;

        if (is_array($nested)) {
            return array_is_list($nested) ? $nested : array_values($nested);
        }

        $data = $payload['data'] ?? null;

        if (is_array($data) && array_is_list($data)) {
            return $data;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function mapRow(array $row): ?ResellPortalOrder
    {
        $id = $row['order_id'] ?? $row['service_id'] ?? $row['id'] ?? null;

        if ($id === null || $id === '') {
            return null;
        }

        $package = is_array($row['package'] ?? null) ? $row['package'] : [];
        $details = is_array($row['esim_details'] ?? null) ? $row['esim_details'] : [];

        $amount = $row['amount_charged'] ?? $row['amount'] ?? $row['price'] ?? null;
        $status = $row['status'] ?? $row['order_status'] ?? $row['esim_status'] ?? ($details['esim_status'] ?? null);
        $createdAt = $row['created_at'] ?? $row['date'] ?? null;

        return new ResellPortalOrder(
            id: (string) $id,
            clientId: isset($row['client_id']) && $row['client_id'] !== '' ? (string) $row['client_id'] : null,
            packageCode: $this->nullableString($row['package_code'] ?? $package['package_code'] ?? $package['code'] ?? null),
            packageName: $this->nullableString($row['package_name'] ?? $package['name'] ?? null),
            location: $this->nullableString($row['location'] ?? $package['location'] ?? null),
            amount: $amount === null || $amount === '' ? null : (string) $amount,
            status: $this->nullableString($status),
            createdAt: $this->nullableString($createdAt),
        );
    }

    protected function nullableString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }
}
