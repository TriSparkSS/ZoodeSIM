<?php

namespace App\Models;

use App\Models\Concerns\FormatsUserBonus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PromoUsage extends Model
{
    use FormatsUserBonus;

    protected $table = 'promo_usage';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'promo_code_id',
        'user_id',
        'partner_id',
        'bonus_mb_given',
        'bonus_type',
        'bonus_amount',
        'partner_reward',
        'used_at',
        'device_id',
        'ip_address',
    ];

    protected $casts = [
        'used_at' => 'datetime',
        'bonus_mb_given' => 'integer',
        'bonus_amount' => 'decimal:2',
        'partner_reward' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (PromoUsage $usage) {
            if (! $usage->id) {
                $usage->id = (string) Str::uuid();
            }
        });

        static::saving(function (PromoUsage $usage) {
            $usage->syncBonusColumns();
        });
    }

    public function syncBonusColumns(): void
    {
        $type = $this->bonus_type === PromoCode::BONUS_TYPE_USD
            ? PromoCode::BONUS_TYPE_USD
            : PromoCode::BONUS_TYPE_MB;
        $this->bonus_type = $type;

        if ($type === PromoCode::BONUS_TYPE_USD) {
            $this->bonus_mb_given = 0;
            $this->bonus_amount = $this->bonus_amount ?? '0.00';

            return;
        }

        if ($this->bonus_amount !== null) {
            $mb = (int) $this->bonus_amount;
            $this->bonus_mb_given = $mb;
            $this->bonus_amount = number_format($mb, 2, '.', '');

            return;
        }

        $mb = (int) ($this->bonus_mb_given ?? 0);
        $this->bonus_mb_given = $mb;
        $this->bonus_amount = number_format($mb, 2, '.', '');
    }

    public function promoCode(): BelongsTo
    {
        return $this->belongsTo(PromoCode::class, 'promo_code_id', 'id');
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'partner_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
