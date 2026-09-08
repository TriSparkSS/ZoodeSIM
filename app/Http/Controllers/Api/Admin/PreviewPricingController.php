<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\PreviewPricingRequest;
use App\Http\Responses\ApiResponse;
use App\Services\Pricing\PricingSlabService;
use Illuminate\Http\JsonResponse;

class PreviewPricingController extends Controller
{
    public function __invoke(PreviewPricingRequest $request, PricingSlabService $slabs): JsonResponse
    {
        $quote = $slabs->preview($request->validated('provider_cost'));

        return ApiResponse::success(__('api.admin.pricing.preview_calculated'), [
            'preview' => $quote->toAdminPreview(),
        ]);
    }
}
