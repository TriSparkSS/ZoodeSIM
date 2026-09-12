<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\BannerResource;
use App\Http\Responses\ApiResponse;
use App\Services\Banner\Contracts\BannerServiceInterface;
use Illuminate\Http\JsonResponse;

class IndexBannerController extends Controller
{
    public function __invoke(BannerServiceInterface $banners): JsonResponse
    {
        return ApiResponse::success(__('api.banners.retrieved'), [
            'banners' => BannerResource::collection($banners->activeForUserApp())->resolve(),
        ]);
    }
}
