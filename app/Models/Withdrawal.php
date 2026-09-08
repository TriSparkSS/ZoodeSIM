<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Withdrawal extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_FAILED = 'failed';

    public const METHOD_CARD = 'card';

    public const METHOD_PAYME = 'payme';

    public const METHOD_CLICK = 'click';

    public const METHOD_CRYPTO = 'crypto';

    public const METHOD_PAYPAL = 'paypal';

    public const METHOD_BANK_TRANSFER = 'bank_transfer';

    protected $table = 'withdrawals';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'partner_id',
        'amount',
        'method',
        'payout_details',
        'status',
        'admin_note',
        'processed_by',
        'requested_at',
        'completed_at',
        'rejected_at',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'completed_at' => 'datetime',
        'rejected_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (Withdrawal $withdrawal) {
            if (! $withdrawal->id) {
                $withdrawal->id = (string) Str::uuid();
            }
        });
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'partner_id', 'id');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'processed_by');
    }

    public function statusBadgeType(): string
    {
        return match ($this->status) {
            self::STATUS_COMPLETED => 'completed',
            self::STATUS_REJECTED, self::STATUS_FAILED => 'rejected',
            self::STATUS_PROCESSING => 'approved',
            default => 'pending',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PROCESSING], true);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isActionable(): bool
    {
        return $this->isOpen();
    }

    public static function methodLabel(string $method): string
    {
        return match ($method) {
            self::METHOD_CARD => __('partner.earnings.card'),
            self::METHOD_PAYME => __('partner.earnings.payme'),
            self::METHOD_CLICK => __('partner.earnings.click'),
            self::METHOD_CRYPTO => __('partner.earnings.crypto'),
            self::METHOD_PAYPAL => __('partner.earnings.paypal'),
            self::METHOD_BANK_TRANSFER => __('partner.earnings.bank_transfer'),
            default => $method,
        };
    }

    /**
     * Methods offered for new payout preferences (TZ §6.2 / §9.2).
     *
     * @return list<string>
     */
    public static function selectableMethods(): array
    {
        return [
            self::METHOD_CARD,
            self::METHOD_PAYME,
            self::METHOD_CLICK,
            self::METHOD_CRYPTO,
        ];
    }

    /**
     * @return list<string>
     */
    public static function methods(): array
    {
        return array_values(array_unique([
            ...self::selectableMethods(),
            self::METHOD_PAYPAL,
            self::METHOD_BANK_TRANSFER,
        ]));
    }
}
