<?php

namespace App\Services\Content;

use App\Models\ContentBlock;

class ContentBlockService
{
    public function find(string $slug): ?ContentBlock
    {
        return ContentBlock::query()
            ->active()
            ->slug($slug)
            ->first();
    }

    public function title(string $slug, ?string $fallback = null): string
    {
        $block = $this->find($slug);

        if (! $block) {
            return $fallback ?? '';
        }

        $title = $block->title;

        return filled($title) ? (string) $title : (string) ($fallback ?? '');
    }

    public function body(string $slug, ?string $fallback = null): string
    {
        $block = $this->find($slug);

        if (! $block) {
            return $fallback ?? '';
        }

        $body = $block->body;

        return filled($body) ? (string) $body : (string) ($fallback ?? '');
    }

    /**
     * @return array{title: string, body: string}
     */
    public function pair(string $slug, ?string $fallbackTitle = null, ?string $fallbackBody = null): array
    {
        return [
            'title' => $this->title($slug, $fallbackTitle),
            'body' => $this->body($slug, $fallbackBody),
        ];
    }
}
