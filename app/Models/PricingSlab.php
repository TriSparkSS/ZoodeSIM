<?php

namespace App\Models;

use App\Support\Money;
use Database\Factories\PricingSlabFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PricingSlab extends Model
{
    /** @use HasFactory<PricingSlabFactory> */
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'min_amount',
        'max_amount',
        'percentage',
        'is_active',
        'priority',
    ];

    protected function casts(): array
    {
        return [
            'min_amount' => 'decimal:2',
            'max_amount' => 'decimal:2',
            'percentage' => 'decimal:2',
            'is_active' => 'boolean',
            'priority' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (PricingSlab $slab) {
            if (! $slab->id) {
                $slab->id = (string) Str::uuid();
            }
        });
    }

    /**
     * Half-open range: min_amount <= cost < max_amount.
     */
    public function matchesCostCents(int $costCents): bool
    {
        return $costCents >= $this->minCents() && $costCents < $this->maxCents();
    }

    public function overlapsRangeCents(int $minCents, int $maxCents): bool
    {
        return $this->minCents() < $maxCents && $minCents < $this->maxCents();
    }

    public function minCents(): int
    {
        return Money::decimalToCents((string) $this->min_amount);
    }

    public function maxCents(): int
    {
        return Money::decimalToCents((string) $this->max_amount);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @return array<string, mixed>
     */
    public function auditValues(): array
    {
        return [
            'min_amount' => (string) $this->min_amount,
            'max_amount' => (string) $this->max_amount,
            'percentage' => (string) $this->percentage,
            'is_active' => $this->is_active,
            'priority' => $this->priority,
        ];
    }
}
