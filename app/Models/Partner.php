<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class Partner extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'partners';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'email',
        'password',
        'social_contacts',
        'status',
        'balance',
        'total_earned',
        'payout_method',
        'payout_details',
        'email_notifications',
        'telegram_notifications',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'social_contacts' => 'array',
            'balance' => 'decimal:2',
            'total_earned' => 'decimal:2',
            'password' => 'hashed',
            'email_notifications' => 'boolean',
            'telegram_notifications' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Partner $partner) {
            if (! $partner->id) {
                $partner->id = (string) Str::uuid();
            }
        });
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function promoCodes(): HasMany
    {
        return $this->hasMany(PromoCode::class, 'partner_id', 'id');
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class, 'partner_id', 'id');
    }

    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'transactable');
    }

    public function authSessions(): MorphMany
    {
        return $this->morphMany(AuthSession::class, 'authenticatable');
    }

    public function authActivityLogs(): MorphMany
    {
        return $this->morphMany(AuthActivityLog::class, 'authenticatable');
    }
}
