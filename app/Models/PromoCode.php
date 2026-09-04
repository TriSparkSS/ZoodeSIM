<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PromoCode extends Model
{
    protected $table = 'promo_codes';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'partner_id',
        'code',
        'bonus_mb',
        'partner_reward',
        'type',
        'expires_at',
        'is_active',
        'usage_count',
        'max_usage',
    ];

    protected $casts = [
        'bonus_mb' => 'integer',
        'partner_reward' => 'decimal:2',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'usage_count' => 'integer',
        'max_usage' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (PromoCode $promo) {
            if (! $promo->id) {
                $promo->id = (string) Str::uuid();
            }
        });
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

    public function isCurrentlyUsable(): bool
    {
        return $this->is_active && ! $this->isExpired() && ! $this->isExhausted();
    }

    /**
     * Lifecycle status for admin UI (not the raw is_active flag).
     *
     * @return 'active'|'expired'|'exhausted'|'inactive'
     */
    public function lifecycleStatus(): string
    {
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
