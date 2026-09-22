<?php

namespace App\Models;

use App\Services\Referral\UserReferralCodeGenerator;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'phone', 'password', 'balance', 'bonus_mb', 'device_id', 'registration_ip', 'firebase_uid', 'auth_provider', 'referral_code', 'referred_by_user_id'])]
#[Hidden(['password', 'remember_token', 'firebase_uid'])]
class User extends Authenticatable
{
    public const AUTH_PASSWORD = 'password';

    public const AUTH_GOOGLE = 'google';

    public const AUTH_APPLE = 'apple';

    public const AUTH_FACEBOOK = 'facebook';

    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @return list<string>
     */
    public static function authProviders(): array
    {
        return [
            self::AUTH_GOOGLE,
            self::AUTH_APPLE,
            self::AUTH_FACEBOOK,
            self::AUTH_PASSWORD,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'balance' => 'decimal:2',
            'bonus_mb' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (! $user->id) {
                $user->id = (string) Str::uuid();
            }

            if (blank($user->referral_code)) {
                $user->referral_code = app(UserReferralCodeGenerator::class)->generate();
            }
        });

        static::updating(function (User $user) {
            if ($user->isDirty('referral_code')) {
                $user->referral_code = $user->getOriginal('referral_code');
            }
        });
    }

    public function referredBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'referred_by_user_id');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(self::class, 'referred_by_user_id');
    }

    public function referralRecords(): HasMany
    {
        return $this->hasMany(UserReferral::class, 'referrer_id');
    }

    public function promoUsage(): HasOne
    {
        return $this->hasOne(PromoUsage::class, 'user_id', 'id');
    }

    public function esimOrders(): HasMany
    {
        return $this->hasMany(EsimOrder::class);
    }

    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'transactable');
    }
}
