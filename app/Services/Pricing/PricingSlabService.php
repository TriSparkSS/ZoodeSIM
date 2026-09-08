<?php

namespace App\Services\Pricing;

use App\DataTransferObjects\EsimPriceQuote;
use App\Models\AuthActivityLog;
use App\Models\PricingSlab;
use App\Services\Auth\AuthActivityLogger;
use App\Support\Money;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class PricingSlabService
{
    public function __construct(
        protected AuthActivityLogger $activity,
        protected PricingService $pricing,
    ) {}

    /**
     * @return Collection<int, PricingSlab>
     */
    public function list(): Collection
    {
        return PricingSlab::query()
            ->orderBy('priority')
            ->orderBy('min_amount')
            ->get();
    }

    /**
     * @param  array{min_amount: string, max_amount: string, percentage: string, priority: int, is_active?: bool}  $data
     */
    public function create(array $data, ?object $admin = null): PricingSlab
    {
        $this->assertValidRange($data['min_amount'], $data['max_amount']);
        $this->assertNoActiveOverlap(
            $data['min_amount'],
            $data['max_amount'],
            (bool) ($data['is_active'] ?? true),
        );

        $slab = PricingSlab::query()->create([
            'min_amount' => Money::normalizeDecimal($data['min_amount']),
            'max_amount' => Money::normalizeDecimal($data['max_amount']),
            'percentage' => Money::normalizeDecimal($data['percentage']),
            'priority' => $data['priority'],
            'is_active' => $data['is_active'] ?? true,
        ]);

        PricingService::forgetCache();

        $this->audit(AuthActivityLog::EVENT_PRICING_SLAB_CREATED, $admin, $slab, [
            'new' => $slab->auditValues(),
        ]);

        return $slab;
    }

    /**
     * @param  array{min_amount?: string, max_amount?: string, percentage?: string, priority?: int, is_active?: bool}  $data
     */
    public function update(PricingSlab $slab, array $data, ?object $admin = null): PricingSlab
    {
        $previous = $slab->auditValues();
        $min = $data['min_amount'] ?? (string) $slab->min_amount;
        $max = $data['max_amount'] ?? (string) $slab->max_amount;
        $isActive = array_key_exists('is_active', $data) ? (bool) $data['is_active'] : $slab->is_active;

        $this->assertValidRange($min, $max);
        $this->assertNoActiveOverlap($min, $max, $isActive, $slab->id);

        $payload = [];

        if (array_key_exists('min_amount', $data)) {
            $payload['min_amount'] = Money::normalizeDecimal($data['min_amount']);
        }

        if (array_key_exists('max_amount', $data)) {
            $payload['max_amount'] = Money::normalizeDecimal($data['max_amount']);
        }

        if (array_key_exists('percentage', $data)) {
            $payload['percentage'] = Money::normalizeDecimal($data['percentage']);
        }

        if (array_key_exists('priority', $data)) {
            $payload['priority'] = $data['priority'];
        }

        if (array_key_exists('is_active', $data)) {
            $payload['is_active'] = $isActive;
        }

        $slab->update($payload);
        $slab->refresh();

        PricingService::forgetCache();

        $event = $this->updateEvent($previous['is_active'], $slab->is_active);
        $this->audit($event, $admin, $slab, [
            'previous' => $previous,
            'new' => $slab->auditValues(),
        ]);

        return $slab;
    }

    public function toggle(PricingSlab $slab, ?object $admin = null): PricingSlab
    {
        return $this->update($slab, ['is_active' => ! $slab->is_active], $admin);
    }

    public function delete(PricingSlab $slab, ?object $admin = null): void
    {
        $previous = $slab->auditValues();
        $id = $slab->id;

        $slab->delete();

        PricingService::forgetCache();

        $this->activity->log(
            'admin',
            AuthActivityLog::EVENT_PRICING_SLAB_DELETED,
            $admin instanceof Authenticatable ? $admin : null,
            meta: [
                'slab_id' => $id,
                'previous' => $previous,
            ],
        );
    }

    public function preview(string|int|float $providerCost, ?string $currency = null): EsimPriceQuote
    {
        return $this->pricing->quoteFromProviderCost($providerCost, $currency);
    }

    protected function assertValidRange(string $minAmount, string $maxAmount): void
    {
        $min = Money::decimalToCents($minAmount);
        $max = Money::decimalToCents($maxAmount);

        if ($max <= $min) {
            throw ValidationException::withMessages([
                'max_amount' => [__('admin.pricing_slabs.validation.max_greater')],
            ]);
        }
    }

    protected function assertNoActiveOverlap(string $minAmount, string $maxAmount, bool $isActive, ?string $ignoreId = null): void
    {
        if (! $isActive) {
            return;
        }

        $min = Money::decimalToCents($minAmount);
        $max = Money::decimalToCents($maxAmount);

        $overlap = PricingSlab::query()
            ->active()
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->get()
            ->first(fn (PricingSlab $slab): bool => $slab->overlapsRangeCents($min, $max));

        if ($overlap) {
            throw ValidationException::withMessages([
                'min_amount' => [__('admin.pricing_slabs.validation.overlap')],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    protected function audit(string $event, ?object $admin, PricingSlab $slab, array $meta): void
    {
        $this->activity->log(
            'admin',
            $event,
            $admin instanceof Authenticatable ? $admin : null,
            meta: [
                'slab_id' => $slab->id,
                ...$meta,
            ],
        );
    }

    protected function updateEvent(bool $wasActive, bool $isActive): string
    {
        if ($wasActive !== $isActive) {
            return $isActive
                ? AuthActivityLog::EVENT_PRICING_SLAB_ACTIVATED
                : AuthActivityLog::EVENT_PRICING_SLAB_DEACTIVATED;
        }

        return AuthActivityLog::EVENT_PRICING_SLAB_UPDATED;
    }
}
