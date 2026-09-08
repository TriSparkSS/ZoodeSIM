<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\StorePricingSlabRequest;
use App\Http\Requests\Api\Admin\UpdatePricingSlabRequest;
use App\Http\Resources\Api\Admin\PricingSlabResource;
use App\Http\Responses\ApiResponse;
use App\Models\Admin;
use App\Models\PricingSlab;
use App\Services\Pricing\PricingSlabService;
use Illuminate\Http\JsonResponse;

class PricingSlabController extends Controller
{
    public function index(PricingSlabService $slabs): JsonResponse
    {
        return ApiResponse::success(__('api.admin.pricing.slabs_retrieved'), [
            'slabs' => PricingSlabResource::collection($slabs->list())->resolve(),
        ]);
    }

    public function store(StorePricingSlabRequest $request, PricingSlabService $slabs): JsonResponse
    {
        $slab = $slabs->create($request->payload(), $this->admin());

        return ApiResponse::success(
            __('api.admin.pricing.slab_created'),
            ['slab' => PricingSlabResource::make($slab)->resolve()],
            201,
        );
    }

    public function update(
        UpdatePricingSlabRequest $request,
        PricingSlab $slab,
        PricingSlabService $slabs,
    ): JsonResponse {
        $slab = $slabs->update($slab, $request->payload(), $this->admin());

        return ApiResponse::success(__('api.admin.pricing.slab_updated'), [
            'slab' => PricingSlabResource::make($slab)->resolve(),
        ]);
    }

    public function destroy(PricingSlab $slab, PricingSlabService $slabs): JsonResponse
    {
        $slabs->delete($slab, $this->admin());

        return ApiResponse::success(__('api.admin.pricing.slab_deleted'));
    }

    protected function admin(): ?Admin
    {
        $admin = request()->user('admin');

        return $admin instanceof Admin ? $admin : null;
    }
}
