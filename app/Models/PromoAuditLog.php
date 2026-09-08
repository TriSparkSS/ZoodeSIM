<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class PromoAuditLog extends Model
{
    public const UPDATED_AT = null;

    public const ACTION_CREATED = 'created';

    public const ACTION_ACTIVATED = 'activated';

    public const ACTION_DEACTIVATED = 'deactivated';

    public const ACTION_REDEEMED = 'redeemed';

    public const ACTION_VALIDATE_FAILED = 'validate_failed';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'promo_code_id',
        'partner_id',
        'actor_type',
        'actor_id',
        'action',
        'code',
        'ip_address',
        'user_agent',
        'meta',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (PromoAuditLog $log) {
            if (! $log->id) {
                $log->id = (string) Str::uuid();
            }
        });
    }

    public function promoCode(): BelongsTo
    {
        return $this->belongsTo(PromoCode::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function actor(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return list<string>
     */
    public static function actions(): array
    {
        return [
            self::ACTION_CREATED,
            self::ACTION_ACTIVATED,
            self::ACTION_DEACTIVATED,
            self::ACTION_REDEEMED,
            self::ACTION_VALIDATE_FAILED,
        ];
    }
}
