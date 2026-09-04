<?php

namespace App\Models;

use App\Models\Concerns\HasJsonTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProgramSetting extends Model
{
    use HasJsonTranslations;

    protected $table = 'program_settings';

    public $incrementing = false;

    protected $keyType = 'string';

    public array $translatable = ['label', 'description'];

    protected $fillable = [
        'id',
        'key',
        'value',
        'label',
        'description',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (ProgramSetting $setting) {
            if (! $setting->id) {
                $setting->id = (string) Str::uuid();
            }
        });
    }
}
