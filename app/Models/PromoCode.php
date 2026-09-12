<?php

namespace App\Models;

use App\Models\Concerns\FormatsUserBonus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PromoCode extends Model
{
    use FormatsUserBonus;

    public const BONUS_TYPE_MB = 'mb';

    public const BONUS_TYPE_USD = 'usd';

    protected $table = 'promo_codes';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'partner_id',
        'code',
        'bonus_mb',
        'bonus_type',
        'bonus_amount',
        'partner_reward',
        'type',
        'expires_at',
        'is_active',
        'usage_count',
        'max_usage',
        'unlock_requirement',
        'unlocked_at',
    ];

    protected $casts = [
        'bonus_mb' => 'integer',
        'bonus_amount' => 'decimal:2',
        'partner_reward' => 'decimal:2',
        'expires_at' => 'datetime',
        'unlocked_at' => 'datetime',
        'is_active' => 'boolean',
        'usage_count' => 'integer',
        'max_usage' => 'integer',
        'unlock_requirement' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (PromoCode $promo) {
            if (! $promo->id) {
                $promo->id = (string) Str::uuid();
            }
        });

        static::saving(function (PromoCode $promo) {
            $promo->syncBonusColumns();
        });
    }

    public function syncBonusColumns(): void
    {
        $type = $this->bonus_type === self::BONUS_TYPE_USD
            ? self::BONUS_TYPE_USD
            : self::BONUS_TYPE_MB;
        $this->bonus_type = $type;

        if ($type === self::BONUS_TYPE_USD) {
            $this->bonus_mb = 0;
            $this->bonus_amount = $this->bonus_amount ?? '0.00';

            return;
        }

        if ($this->bonus_amount !== null) {
            $mb = (int) $this->bonus_amount;
            $this->bonus_mb = $mb;
            $this->bonus_amount = number_format($mb, 2, '.', '');

            return;
        }

        $mb = (int) ($this->bonus_mb ?? 0);
        $this->bonus_mb = $mb;
        $this->bonus_amount = number_format($mb, 2, '.', '');
    }

    /**
     * @return list<string>
     */
    public static function bonusTypes(): array
    {
        return [self::BONUS_TYPE_MB, self::BONUS_TYPE_USD];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'partner_id', 'id');
    }

    public function promoUsage(): HasMany
    {
        return $this->hasMany(PromoUsage::class, 'promo_code_id', 'id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isExhausted(): bool
    {
        return $this->max_usage !== null && $this->usage_count >= $this->max_usage;
    }

    public function isLocked(): bool
    {
        return $this->unlock_requirement !== null
            && (int) $this->unlock_requirement > 0
            && $this->unlocked_at === null;
    }

    public function isCurrentlyUsable(): bool
    {
        return $this->is_active && ! $this->isLocked() && ! $this->isExpired() && ! $this->isExhausted();
    }

    /**
     * Lifecycle status for admin UI (not the raw is_active flag).
     *
     * @return 'active'|'expired'|'exhausted'|'inactive'|'locked'
     */
    public function lifecycleStatus(): string
    {
        if ($this->isLocked()) {
            return 'locked';
        }

        if (! $this->is_active) {
            return 'inactive';
        }

        if ($this->isExpired()) {
            return 'expired';
        }

        if ($this->isExhausted()) {
            return 'exhausted';
        }

        return 'active';
    }
}
