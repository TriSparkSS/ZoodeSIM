<?php

namespace App\Models;

use App\Models\Concerns\HasJsonTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ContentBlock extends Model
{
    use HasJsonTranslations;

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
}
