<?php

namespace App\Services\Esim;

use App\Models\EsimOrder;
use Illuminate\Database\Eloquent\Builder;

class EsimOrderQueryService
{
    /**
     * @param  array{
     *     client_id?: string,
     *     status?: string,
     *     payment_status?: string,
     *     package_code?: string,
     *     location?: string,
     *     date_from?: string,
     *     date_to?: string,
     *     user?: string
     * }  $filters
     * @return Builder<EsimOrder>
     */
    public function filteredQuery(array $filters): Builder
    {
        return EsimOrder::query()
            ->when(filled($filters['client_id'] ?? null), function (Builder $query) use ($filters) {
                $query->where('resellportal_client_id', trim((string) $filters['client_id']));
            })
            ->when(filled($filters['status'] ?? null), function (Builder $query) use ($filters) {
                $query->where('order_status', $filters['status']);
            })
            ->when(filled($filters['payment_status'] ?? null), function (Builder $query) use ($filters) {
                $query->where('payment_status', $filters['payment_status']);
            })
            ->when(filled($filters['package_code'] ?? null), function (Builder $query) use ($filters) {
                $query->where('package_code', 'like', '%'.trim((string) $filters['package_code']).'%');
            })
            ->when(filled($filters['location'] ?? null), function (Builder $query) use ($filters) {
                $query->where('package_location', strtoupper(trim((string) $filters['location'])));
            })
            ->when(filled($filters['date_from'] ?? null), function (Builder $query) use ($filters) {
                $query->whereDate('created_at', '>=', $filters['date_from']);
            })
            ->when(filled($filters['date_to'] ?? null), function (Builder $query) use ($filters) {
                $query->whereDate('created_at', '<=', $filters['date_to']);
            })
            ->when(filled($filters['user'] ?? null), function (Builder $query) use ($filters) {
                $term = '%'.trim((string) $filters['user']).'%';
                $query->whereHas('user', function (Builder $owner) use ($term) {
                    $owner->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('phone', 'like', $term);
                });
            });
    }
}
