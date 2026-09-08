<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class EsimOrder extends Model
{
    public const STATUS_PENDING_PAYMENT = 'pending_payment';

    public const STATUS_PAID = 'paid';

    public const STATUS_PROVISIONING = 'provisioning';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_FAILED = 'failed';

    public const PAYMENT_PENDING = 'pending';

    public const PAYMENT_PAID = 'paid';

    public const PAYMENT_FAILED = 'failed';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'idempotency_key',
        'resellportal_client_id',
        'package_code',
        'package_name',
        'package_location',
        'package_data_volume',
        'package_duration',
        'provider_cost',
        'markup_percentage',
        'markup_amount',
        'customer_price',
        'discount_percentage',
        'discount_amount',
        'charged_amount',
        'currency',
        'payment_status',
        'order_status',
        'resellportal_service_id',
        'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'provider_cost' => 'decimal:2',
            'markup_percentage' => 'decimal:2',
            'markup_amount' => 'decimal:2',
            'customer_price' => 'decimal:2',
            'discount_percentage' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'charged_amount' => 'decimal:2',
            'package_duration' => 'integer',
            'resellportal_client_id' => 'integer',
            'resellportal_service_id' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (EsimOrder $order) {
            if (! $order->id) {
                $order->id = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function detail(): HasOne
    {
        return $this->hasOne(EsimOrderDetail::class);
    }

    public function isActive(): bool
    {
        return $this->order_status === self::STATUS_ACTIVE;
    }

    public function isFailed(): bool
    {
        return $this->order_status === self::STATUS_FAILED;
    }
}
