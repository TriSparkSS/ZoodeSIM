<?php

namespace App\Services\Wallet;

use App\Models\Partner;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class TransactionQueryService
{
    /**
     * @return LengthAwarePaginator<int, Transaction>
     */
    public function paginateForUser(User $user, int $perPage): LengthAwarePaginator
    {
        return $user->transactions()
            ->where('currency', '!=', 'MB')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * @param  array{
     *     transaction_id?: string,
     *     user?: string,
     *     partner?: string,
     *     category?: string,
     *     type?: string,
     *     status?: string,
     *     date_from?: string,
     *     date_to?: string,
     *     amount_min?: string,
     *     amount_max?: string,
     *     promo?: string
     * }  $filters
     * @return Builder<Transaction>
     */
    public function filteredQuery(array $filters): Builder
    {
        return Transaction::query()
            ->with(['transactable', 'promoCode:id,code'])
            ->where('currency', '!=', 'MB')
            ->when(filled($filters['transaction_id'] ?? null), function (Builder $query) use ($filters) {
                $query->where('transaction_id', 'like', '%'.trim((string) $filters['transaction_id']).'%');
            })
            ->when(filled($filters['type'] ?? null), function (Builder $query) use ($filters) {
                $query->where('type', $filters['type']);
            })
            ->when(filled($filters['category'] ?? null), function (Builder $query) use ($filters) {
                $query->where('category', $filters['category']);
            })
            ->when(filled($filters['status'] ?? null), function (Builder $query) use ($filters) {
                $query->where('status', $filters['status']);
            })
            ->when(filled($filters['date_from'] ?? null), function (Builder $query) use ($filters) {
                $query->whereDate('created_at', '>=', $filters['date_from']);
            })
            ->when(filled($filters['date_to'] ?? null), function (Builder $query) use ($filters) {
                $query->whereDate('created_at', '<=', $filters['date_to']);
            })
            ->when(filled($filters['amount_min'] ?? null), function (Builder $query) use ($filters) {
                $query->where('amount', '>=', $filters['amount_min']);
            })
            ->when(filled($filters['amount_max'] ?? null), function (Builder $query) use ($filters) {
                $query->where('amount', '<=', $filters['amount_max']);
            })
            ->when(filled($filters['user'] ?? null), function (Builder $query) use ($filters) {
                $term = '%'.trim((string) $filters['user']).'%';
                $query->where('transactable_type', (new User)->getMorphClass())
                    ->whereHasMorph('transactable', [User::class], function (Builder $owner) use ($term) {
                        $owner->where('name', 'like', $term)
                            ->orWhere('email', 'like', $term)
                            ->orWhere('phone', 'like', $term);
                    });
            })
            ->when(filled($filters['partner'] ?? null), function (Builder $query) use ($filters) {
                $term = '%'.trim((string) $filters['partner']).'%';
                $query->where('transactable_type', (new Partner)->getMorphClass())
                    ->whereHasMorph('transactable', [Partner::class], function (Builder $owner) use ($term) {
                        $owner->where('name', 'like', $term)
                            ->orWhere('email', 'like', $term);
                    });
            })
            ->when(filled($filters['promo'] ?? null), function (Builder $query) use ($filters) {
                $term = '%'.trim((string) $filters['promo']).'%';
                $query->where(function (Builder $inner) use ($term) {
                    $inner->whereIn('category', [
                        Transaction::CATEGORY_PROMO_BONUS,
                        Transaction::CATEGORY_PROMO_REWARD,
                    ])->where(function (Builder $promo) use ($term) {
                        $promo->where('description', 'like', $term)
                            ->orWhereHas('promoCode', fn (Builder $code) => $code->where('code', 'like', $term))
                            ->orWhere('meta', 'like', $term);
                    });
                });
            });
    }
}
