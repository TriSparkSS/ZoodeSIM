<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PartnerApplication extends Model
{
    protected $table = 'partner_applications';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'platforms',
        'instagram',
        'telegram',
        'tiktok',
        'youtube',
        'followers',
        'niche',
        'country',
        'about',
        'status',
        'partner_id',
    ];

    protected $casts = [
        'platforms' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (PartnerApplication $application) {
            if (! $application->id) {
                $application->id = (string) Str::uuid();
            }
        });
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'partner_id', 'id');
    }
}

