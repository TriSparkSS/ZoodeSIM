<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Country extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'code',
        'name',
        'image_path',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Country $country) {
            if (! $country->id) {
                $country->id = (string) Str::uuid();
            }

            if (is_string($country->code)) {
                $country->code = strtoupper($country->code);
            }
        });

        static::updating(function (Country $country) {
            if ($country->isDirty('code') && is_string($country->code)) {
                $country->code = strtoupper($country->code);
            }
        });
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function imageUrl(): ?string
    {
        $path = $this->image_path;

        if (! is_string($path) || $path === '') {
            return null;
        }

        if (str_starts_with($path, 'images/')) {
            return asset($path);
        }

        return Storage::disk('public')->url($path);
    }

    public function storesUploadedImage(): bool
    {
        return is_string($this->image_path) && str_starts_with($this->image_path, 'countries/');
    }
}
