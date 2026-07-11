<?php

namespace App\Http\Controllers;

use App\Services\Analytics\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function store(Request $request, AnalyticsService $analyticsService): JsonResponse
    {
        $validated = $request->validate([
            'event_name' => ['required', 'string', 'max:120'],
            'module' => ['nullable', 'string', 'max:120'],
            'page' => ['nullable', 'string', 'max:255'],
            'route_name' => ['nullable', 'string', 'max:255'],
            'url' => ['nullable', 'string', 'max:2048'],
            'properties' => ['nullable', 'array'],
        ]);

        $analyticsService->track($validated['event_name'], [
            'module' => $validated['module'] ?? null,
            'page' => $validated['page'] ?? null,
            'route_name' => $validated['route_name'] ?? null,
            'url' => $validated['url'] ?? null,
            'properties' => $validated['properties'] ?? [],
        ]);

        return response()->json(['tracked' => true]);
    }
}
