<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Services\Farmer\FarmerDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Resources\Farmer\DashboardResource;

class DashboardController extends FarmerApiController
{
    public function __construct(
        private readonly FarmerDashboardService $dashboardService
    ) {
    }

    public function show(Request $request): JsonResponse
    {
        $payload = $this->dashboardService->build($request->user('farmer_pwa'));

        return $this->success(new DashboardResource($payload));
    }
}
