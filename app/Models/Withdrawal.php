<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Withdrawal extends Model
{
    protected $table = 'withdrawals';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'partner_id',
        'amount',
        'method',
        'status',
        'requested_at',
        'completed_at',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'completed_at' => 'datetime',
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
}

