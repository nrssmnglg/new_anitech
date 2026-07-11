<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Resources\Farmer\NotificationResource;
use App\Services\Farmer\FarmerNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends FarmerApiController
{
    public function __construct(
        private readonly FarmerNotificationService $notificationService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $farmer = $request->user('farmer_pwa')->farmer;
        $module = $request->string('module')->toString() ?: null;
        $unreadOnly = $request->boolean('unread_only');
        $notifications = $this->notificationService->list($farmer, (int) $request->integer('limit', 20), $module, $unreadOnly);

        return $this->success(NotificationResource::collection($notifications), [
            'summary' => $this->notificationService->summary($farmer),
            'filters' => [
                'module' => $module,
                'unread_only' => $unreadOnly,
            ],
        ]);
    }

    public function markAsRead(Request $request, string $notification): JsonResponse
    {
        $farmer = $request->user('farmer_pwa')->farmer;
        $updated = $this->notificationService->markAsRead($farmer, (int) $notification);

        return $this->success([
            'updated' => $updated,
        ], [
            'summary' => $this->notificationService->summary($farmer),
        ]);
    }
}
