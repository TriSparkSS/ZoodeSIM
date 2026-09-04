<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PromoUsage extends Model
{
    protected $table = 'promo_usage';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'promo_code_id',
        'user_id',
        'partner_id',
        'bonus_mb_given',
        'partner_reward',
        'used_at',
    ];

    protected $casts = [
        'used_at' => 'datetime',
        'bonus_mb_given' => 'integer',
        'partner_reward' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (PromoUsage $usage) {
            if (! $usage->id) {
                $usage->id = (string) Str::uuid();
            }
        });
    }

    public function promoCode(): BelongsTo
    {
        return $this->belongsTo(PromoCode::class, 'promo_code_id', 'id');
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'partner_id', 'id');
    }
}

