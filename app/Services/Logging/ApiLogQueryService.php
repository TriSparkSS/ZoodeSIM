<?php

namespace App\Services\Logging;

use App\Models\ApiLog;
use Illuminate\Database\Eloquent\Builder;

class ApiLogQueryService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<ApiLog>
     */
    public function filteredQuery(array $filters): Builder
    {
        return ApiLog::query()
            ->when($this->filled($filters, 'type'), fn (Builder $query) => $query->where('type', $filters['type']))
            ->when($this->filled($filters, 'service'), fn (Builder $query) => $query->where('service', $filters['service']))
            ->when($this->filled($filters, 'method'), fn (Builder $query) => $query->where('method', strtoupper((string) $filters['method'])))
            ->when($this->filled($filters, 'status'), function (Builder $query) use ($filters) {
                $query->where('response_status', (int) $filters['status']);
            })
            ->when($this->filled($filters, 'endpoint'), function (Builder $query) use ($filters) {
                $query->where('endpoint', 'like', '%'.$filters['endpoint'].'%');
            })
            ->when($this->filled($filters, 'user'), function (Builder $query) use ($filters) {
                $term = $filters['user'];
                $query->where(function (Builder $inner) use ($term) {
                    $inner->where('user_id', $term)
                        ->orWhereHas('user', function (Builder $user) use ($term) {
                            $user->where('email', 'like', '%'.$term.'%')
                                ->orWhere('name', 'like', '%'.$term.'%');
                        });
                });
            })
            ->when($this->filled($filters, 'date_from'), function (Builder $query) use ($filters) {
                $query->whereDate('created_at', '>=', $filters['date_from']);
            })
            ->when($this->filled($filters, 'date_to'), function (Builder $query) use ($filters) {
                $query->whereDate('created_at', '<=', $filters['date_to']);
            })
            ->when(! empty($filters['failed']), fn (Builder $query) => $query->failed())
            ->when(! empty($filters['slow']), fn (Builder $query) => $query->slow());
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function filled(array $filters, string $key): bool
    {
        return isset($filters[$key]) && $filters[$key] !== '' && $filters[$key] !== null;
    }
}
