<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Resources\Farmer\FarmerAccountResource;
use App\Http\Resources\Farmer\FarmerProfileResource;
use App\Services\Farmer\FarmerProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends FarmerApiController
{
    public function __construct(
        private readonly FarmerProfileService $profileService
    ) {
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success([
            'account' => (new FarmerAccountResource($request->user('farmer_pwa')))->resolve(),
        ]);
    }

    public function show(Request $request): JsonResponse
    {
        $farmer = $this->profileService->farmerForUser($request->user('farmer_pwa'));

        return $this->success(new FarmerProfileResource($farmer), [
            'options' => $this->profileService->options(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $farmer = $request->user('farmer_pwa')->farmer;
        $updated = $this->profileService->updateProfile($farmer, $request->only([
            'birth_date',
            'civil_status',
            'mobile_number',
            'address',
        ]));

        return $this->success(new FarmerProfileResource($updated), [], 'Profile updated successfully.');
    }
}
