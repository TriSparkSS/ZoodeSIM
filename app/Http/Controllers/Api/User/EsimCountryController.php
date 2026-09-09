<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\CountryResource;
use App\Http\Responses\ApiResponse;
use App\Services\Country\Contracts\CountryServiceInterface;
use Illuminate\Http\JsonResponse;

class EsimCountryController extends Controller
{
    public function __invoke(CountryServiceInterface $countries): JsonResponse
    {
        return ApiResponse::success(__('api.esim.countries_retrieved'), [
            'countries' => CountryResource::collection($countries->active())->resolve(),
        ]);
    }
}
