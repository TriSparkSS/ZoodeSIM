<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class EsimOrderDetail extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'esim_order_id',
        'service_id',
        'iccid',
        'qr_code_url',
        'activation_url',
        'esim_status',
    ];

    protected static function booted(): void
    {
        static::creating(function (EsimOrderDetail $detail) {
            if (! $detail->id) {
                $detail->id = (string) Str::uuid();
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(EsimOrder::class, 'esim_order_id');
    }
}
