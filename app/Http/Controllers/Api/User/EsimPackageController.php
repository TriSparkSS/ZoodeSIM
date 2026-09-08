<?php

namespace App\Http\Controllers\Api\User;

use App\DataTransferObjects\EsimPackageData;
use App\DataTransferObjects\PricedEsimPackage;
use App\Exceptions\PricingUnavailableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\User\ListEsimPackagesRequest;
use App\Http\Resources\Api\EsimPackageResource;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use App\Services\Esim\Contracts\EsimPackageServiceInterface;
use App\Services\Pricing\Contracts\PricingServiceInterface;
use App\Services\Referral\Contracts\PurchaseOfferServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class EsimPackageController extends Controller
{
    public function __invoke(
        ListEsimPackagesRequest $request,
        EsimPackageServiceInterface $packages,
        PricingServiceInterface $pricing,
        PurchaseOfferServiceInterface $offers,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        $items = $packages->list($request->validated('country'))
            ->map(function (EsimPackageData $package) use ($pricing, $offers, $user): ?PricedEsimPackage {
                try {
                    return PricedEsimPackage::fromOffer(
                        $package,
                        $offers->offerFor($user, $pricing->quote($package)),
                    );
                } catch (PricingUnavailableException) {
                    Log::warning('eSIM package excluded from catalog: no pricing slab', [
                        'package_code' => $package->packageCode,
                    ]);

                    return null;
                }
            })
            ->filter()
            ->values();

        return ApiResponse::success(__('api.esim.packages_retrieved'), [
            'packages' => EsimPackageResource::collection($items)->resolve(),
        ]);
    }
}
