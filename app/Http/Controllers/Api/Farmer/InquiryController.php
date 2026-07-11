<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Requests\Farmer\ReplyInquiryRequest;
use App\Http\Requests\Farmer\StoreInquiryRequest;
use App\Http\Resources\Farmer\InquiryResource;
use App\Http\Resources\Farmer\InquiryThreadResource;
use App\Models\Attachment;
use App\Models\Query;
use App\Models\QueryResponse;
use App\Services\Farmer\FarmerInquiryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InquiryController extends FarmerApiController
{
    public function __construct(
        private readonly FarmerInquiryService $inquiryService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $farmer = $request->user('farmer_pwa')->farmer;
        $archived = $request->boolean('archived');
        $status = $request->string('status')->toString() ?: null;
        $inquiries = $this->inquiryService->list($farmer, $archived, (int) $request->integer('per_page', 10), $status);

        return $this->paginated($inquiries, InquiryResource::collection($inquiries), [
            'summary' => $this->inquiryService->summary($farmer),
            'filters' => [
                'archived' => $archived,
                'status' => $status,
            ],
            'categories' => $this->inquiryService->categories(),
            'templates' => $this->inquiryService->templates(),
        ]);
    }

    public function store(StoreInquiryRequest $request): JsonResponse
    {
        $record = $this->inquiryService->create(
            $request->user('farmer_pwa')->farmer,
            $request->user('farmer_pwa'),
            $request->validated(),
            $request->allFiles(),
        );

        return $this->success(new InquiryResource($record), [], 'Inquiry submitted successfully.', 201);
    }

    public function show(Request $request, Query $inquiry): JsonResponse
    {
        $this->authorize('view', $inquiry);
        $record = $this->inquiryService->thread($request->user('farmer_pwa')->farmer, $inquiry->id);

        return $this->success(new InquiryThreadResource($record));
    }

    public function reply(ReplyInquiryRequest $request, Query $inquiry): JsonResponse
    {
        $this->authorize('reply', $inquiry);
        $record = $this->inquiryService->reply(
            $request->user('farmer_pwa')->farmer,
            $request->user('farmer_pwa'),
            $inquiry->id,
            $request->validated(),
            $request->allFiles(),
        );

        return $this->success(new InquiryThreadResource($record), [], 'Reply sent successfully.');
    }

    public function showAttachment(Request $request, Query $inquiry, Attachment $attachment): StreamedResponse
    {
        $this->authorize('view', $inquiry);
        abort_unless(
            $attachment->module_type === Query::class && (int) $attachment->module_id === (int) $inquiry->id,
            404,
        );

        return $this->streamAttachment((string) $attachment->file_path, $attachment->original_name);
    }

    public function showResponseAttachment(Request $request, Query $inquiry, QueryResponse $response, Attachment $attachment): StreamedResponse
    {
        $this->authorize('view', $inquiry);
        abort_unless((int) $response->query_id === (int) $inquiry->id, 404);
        abort_unless(
            $attachment->module_type === QueryResponse::class && (int) $attachment->module_id === (int) $response->id,
            404,
        );

        return $this->streamAttachment((string) $attachment->file_path, $attachment->original_name);
    }

    private function streamAttachment(string $path, ?string $originalName): StreamedResponse
    {
        $filename = $originalName ?: basename($path);
        $mimeType = Storage::disk('public')->mimeType($path) ?: 'application/octet-stream';

        return Storage::disk('public')->response(
            $path,
            $filename,
            ['Content-Type' => $mimeType],
            'inline',
        );
    }
}
