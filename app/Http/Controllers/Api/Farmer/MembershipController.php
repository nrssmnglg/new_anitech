<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Resources\Farmer\MembershipResource;
use App\Services\Farmer\FarmerMembershipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MembershipController extends FarmerApiController
{
    public function __construct(
        private readonly FarmerMembershipService $membershipService
    ) {
    }

    public function show(Request $request): JsonResponse
    {
        $membership = $this->membershipService->membership($request->user('farmer_pwa')->farmer);

        return $this->success(new MembershipResource($membership));
    }
}
