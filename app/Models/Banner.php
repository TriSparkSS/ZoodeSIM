<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Banner extends Model
{
    public const DISPLAY_USER_APP = 'user_app';

    public const DISPLAY_PARTNER_PANEL = 'partner_panel';

    public const DISPLAY_BOTH = 'both';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'title',
        'image_path',
        'link_url',
        'display_on',
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
        static::creating(function (Banner $banner) {
            if (! $banner->id) {
                $banner->id = (string) Str::uuid();
            }
        });
    }

    /**
     * @return list<string>
     */
    public static function displayTargets(): array
    {
        return [
            self::DISPLAY_USER_APP,
            self::DISPLAY_PARTNER_PANEL,
            self::DISPLAY_BOTH,
        ];
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
        return $query->orderBy('sort_order')->orderBy('created_at');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForUserApp(Builder $query): Builder
    {
        return $query->whereIn('display_on', [self::DISPLAY_USER_APP, self::DISPLAY_BOTH]);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForPartnerPanel(Builder $query): Builder
    {
        return $query->whereIn('display_on', [self::DISPLAY_PARTNER_PANEL, self::DISPLAY_BOTH]);
    }

    public function imageUrl(): ?string
    {
        $path = $this->image_path;

        if (! is_string($path) || $path === '') {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    public function storesUploadedImage(): bool
    {
        return is_string($this->image_path) && str_starts_with($this->image_path, 'banners/');
    }
}
