<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\ContentBlock;
use App\Services\Content\ContentBlockService;
use Illuminate\Http\JsonResponse;

class ShowLegalPageController extends Controller
{
    public function __invoke(string $page, ContentBlockService $blocks): JsonResponse
    {
        $slug = ContentBlock::legalSlugForPage($page);
        $block = $slug ? $blocks->find($slug) : null;

        if (! $block) {
            return ApiResponse::error(__('api.legal.not_found'), [], 404);
        }

        return ApiResponse::success(__('api.legal.retrieved'), [
            'slug' => $slug,
            'page' => $page,
            'title' => (string) $block->title,
            'body' => (string) ($block->body ?? ''),
        ]);
    }
}
