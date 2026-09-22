<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class UserReferral extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'referrer_id',
        'referred_id',
        'referrer_amount',
        'referred_amount',
        'currency',
    ];

    protected function casts(): array
    {
        return [
            'referrer_amount' => 'decimal:2',
            'referred_amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (UserReferral $referral) {
            if (! $referral->id) {
                $referral->id = (string) Str::uuid();
            }
        });
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referred(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_id');
    }
}
