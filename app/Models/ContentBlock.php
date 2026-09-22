<?php

namespace App\Models;

use App\Models\Concerns\HasJsonTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ContentBlock extends Model
{
    use HasJsonTranslations;

    public const LEGAL_PRIVACY = 'legal.privacy';

    public const LEGAL_TERMS = 'legal.terms';

    public const LEGAL_DELETE_ACCOUNT = 'legal.delete_account';

    /**
     * Public page key => content-block slug.
     *
     * @var array<string, string>
     */
    public const LEGAL_PAGES = [
        'privacy' => self::LEGAL_PRIVACY,
        'terms' => self::LEGAL_TERMS,
        'delete-account' => self::LEGAL_DELETE_ACCOUNT,
    ];

    protected $table = 'content_blocks';

    public $incrementing = false;

    protected $keyType = 'string';

    public array $translatable = ['title', 'body'];

    protected $fillable = [
        'id',
        'slug',
        'title',
        'body',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (ContentBlock $block) {
            if (! $block->id) {
                $block->id = (string) Str::uuid();
            }
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSlug($query, string $slug)
    {
        return $query->where('slug', $slug);
    }

    public function scopeLegal($query)
    {
        return $query->whereIn('slug', array_values(self::LEGAL_PAGES));
    }

    public static function legalSlugForPage(string $page): ?string
    {
        return self::LEGAL_PAGES[$page] ?? null;
    }

    /**
     * @return list<string>
     */
    public static function legalSlugs(): array
    {
        return array_values(self::LEGAL_PAGES);
    }
}
