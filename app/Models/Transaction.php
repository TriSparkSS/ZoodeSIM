<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class Transaction extends Model
{
    public const TYPE_CREDIT = 'credit';

    public const TYPE_DEBIT = 'debit';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_PENDING = 'pending';

    public const STATUS_FAILED = 'failed';

    public const CATEGORY_PROMO_BONUS = 'promo_bonus';

    public const CATEGORY_PROMO_REWARD = 'promo_reward';

    public const CATEGORY_WITHDRAWAL_HOLD = 'withdrawal_hold';

    public const CATEGORY_WITHDRAWAL_REFUND = 'withdrawal_refund';

    public const CATEGORY_ADMIN_CREDIT = 'admin_credit';

    public const CATEGORY_ADMIN_DEBIT = 'admin_debit';

    public const CATEGORY_WALLET_CREDIT = 'wallet_credit';

    public const CATEGORY_WALLET_DEBIT = 'wallet_debit';

    public const CATEGORY_ESIM_PURCHASE = 'esim_purchase';

    public const CATEGORY_PURCHASE_CASHBACK = 'purchase_cashback';

    public const CATEGORY_PURCHASE_COMMISSION = 'purchase_commission';

    public const CATEGORY_REFERRAL_MILESTONE = 'referral_milestone';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'transaction_id',
        'transactable_type',
        'transactable_id',
        'type',
        'category',
        'amount',
        'balance_before',
        'balance_after',
        'currency',
        'status',
        'reference_type',
        'reference_id',
        'promo_code_id',
        'description',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'balance_before' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'meta' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Transaction $transaction) {
            if (! $transaction->id) {
                $transaction->id = (string) Str::uuid();
            }
        });
    }

    public function transactable(): MorphTo
    {
        return $this->morphTo();
    }

    public function promoCode(): BelongsTo
    {
        return $this->belongsTo(PromoCode::class);
    }

    public function isCredit(): bool
    {
        return $this->type === self::TYPE_CREDIT;
    }

    /**
     * @return list<string>
     */
    public static function types(): array
    {
        return [self::TYPE_CREDIT, self::TYPE_DEBIT];
    }

    /**
     * @return list<string>
     */
    public static function statuses(): array
    {
        return [self::STATUS_COMPLETED, self::STATUS_PENDING, self::STATUS_FAILED];
    }

    /**
     * @return list<string>
     */
    public static function categories(): array
    {
        return [
            self::CATEGORY_PROMO_BONUS,
            self::CATEGORY_PROMO_REWARD,
            self::CATEGORY_WITHDRAWAL_HOLD,
            self::CATEGORY_WITHDRAWAL_REFUND,
            self::CATEGORY_ADMIN_CREDIT,
            self::CATEGORY_ADMIN_DEBIT,
            self::CATEGORY_WALLET_CREDIT,
            self::CATEGORY_WALLET_DEBIT,
            self::CATEGORY_ESIM_PURCHASE,
            self::CATEGORY_PURCHASE_CASHBACK,
            self::CATEGORY_PURCHASE_COMMISSION,
            self::CATEGORY_REFERRAL_MILESTONE,
        ];
    }
}
