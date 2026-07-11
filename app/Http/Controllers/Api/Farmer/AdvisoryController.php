<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Resources\Farmer\AdvisoryResource;
use App\Models\Advisory;
use App\Models\Attachment;
use App\Models\AdvisoryReaction;
use App\Models\Farmer;
use App\Services\Farmer\FarmerAdvisoryService;
use App\Services\Routing\PublicRouteKeyService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdvisoryController extends FarmerApiController
{
    public function __construct(
        private readonly FarmerAdvisoryService $advisoryService,
        private readonly PublicRouteKeyService $publicRouteKeyService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $farmer = $request->user('farmer_pwa')->farmer;
        $advisories = $this->advisoryService->queryForFarmer($farmer)
            ->with([
                'barangay:id,name',
                'memberType:id,code,name',
                'reactions' => fn ($query) => $query->where('farmer_id', $farmer->id),
            ])
            ->orderByDesc('published_at')
            ->paginate((int) $request->integer('per_page', 10));

        return $this->paginated($advisories, AdvisoryResource::collection($advisories));
    }

    public function show(Request $request, string $advisory): JsonResponse
    {
        $farmer = $request->user('farmer_pwa')->farmer;
        $record = $this->resolveAdvisoryForFarmer($farmer, $advisory, ['attachments', 'barangay:id,name', 'memberType:id,code,name']);

        return $this->success(new AdvisoryResource($record));
    }

    public function react(Request $request, string $advisory): JsonResponse
    {
        $request->validate([
            'reaction' => ['required', 'in:like,dislike'],
        ]);

        $farmer = $request->user('farmer_pwa')->farmer;
        $record = $this->resolveAdvisoryForFarmer($farmer, $advisory);
        $nextReaction = (string) $request->string('reaction');

        DB::transaction(function () use ($record, $farmer, $nextReaction): void {
            $existing = AdvisoryReaction::query()
                ->where('advisory_id', $record->id)
                ->where('farmer_id', $farmer->id)
                ->first();

            if ($existing && $existing->reaction === $nextReaction) {
                $existing->delete();
                $this->applyReactionDelta($record, $nextReaction, -1);
                return;
            }

            if ($existing) {
                $previousReaction = $existing->reaction;
                $existing->update(['reaction' => $nextReaction]);
                $this->applyReactionDelta($record, $previousReaction, -1);
                $this->applyReactionDelta($record, $nextReaction, 1);
                return;
            }

            AdvisoryReaction::query()->create([
                'advisory_id' => $record->id,
                'farmer_id' => $farmer->id,
                'reaction' => $nextReaction,
            ]);

            $this->applyReactionDelta($record, $nextReaction, 1);
        });

        $record = $this->resolveAdvisoryForFarmer($farmer, $advisory, ['barangay:id,name', 'memberType:id,code,name']);

        return $this->success(
            new AdvisoryResource($record),
            [],
            'Reaction updated.',
        );
    }

    public function attachment(Request $request, string $advisory, Attachment $attachment): StreamedResponse
    {
        $farmer = $request->user('farmer_pwa')->farmer;
        $record = $this->resolveAdvisoryForFarmer($farmer, $advisory);

        if ((int) $attachment->module_id !== (int) $record->id || $attachment->module_type !== $record->getMorphClass()) {
            throw new NotFoundHttpException();
        }

        /** @var Filesystem $filesystem */
        $filesystem = Storage::disk('public');
        $filename = $attachment->original_name ?: basename((string) $attachment->file_path);

        return $filesystem->response(
            (string) $attachment->file_path,
            $filename,
            [],
            $this->isInlineAttachment($filename) ? 'inline' : 'attachment',
        );
    }

    private function isInlineAttachment(string $filename): bool
    {
        return in_array(strtolower(pathinfo($filename, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'], true);
    }

    private function resolveAdvisoryForFarmer(Farmer $farmer, string $advisory, array $with = []): Advisory
    {
        $decodedId = is_numeric($advisory) ? (int) $advisory : $this->publicRouteKeyService->decode($advisory);

        return $this->advisoryService->queryForFarmer($farmer)
            ->with(array_merge($with, [
                'reactions' => fn ($query) => $query->where('farmer_id', $farmer->id),
            ]))
            ->where(function (Builder $query) use ($advisory, $decodedId): void {
                $query->where('slug', $advisory);

                if ($decodedId !== null) {
                    $query->orWhereKey($decodedId);
                }
            })
            ->firstOrFail();
    }

    private function applyReactionDelta(Advisory $advisory, string $reaction, int $delta): void
    {
        $column = $reaction === 'dislike' ? 'dislike_count' : 'like_count';
        $current = (int) $advisory->getAttribute($column);
        $advisory->forceFill([
            $column => max(0, $current + $delta),
        ])->save();
    }
}
