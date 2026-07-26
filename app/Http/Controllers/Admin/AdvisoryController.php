<?php

namespace App\Http\Controllers\Admin;

use App\Enums\NotificationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdvisoryRequest;
use App\Http\Requests\Admin\UpdateAdvisoryRequest;
use App\Models\Advisory;
use App\Models\Attachment;
use App\Models\Barangay;
use App\Models\Farmer;
use App\Models\MemberType;
use App\Models\User;
use App\Services\Analytics\AnalyticsService;
use App\Services\Advisories\AdvisoryPublicationService;
use App\Services\Audit\AuditTrailService;
use App\Services\Notifications\NotificationDispatchService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdvisoryController extends Controller
{
    public function __construct(
        private readonly AdvisoryPublicationService $publicationService,
        private readonly NotificationDispatchService $notificationDispatchService,
        private readonly AuditTrailService $auditTrailService,
        private readonly AnalyticsService $analyticsService,
    ) {
        $this->middleware('role.in:' . User::ROLE_ADMIN . ',' . User::ROLE_STAFF);
    }

    public function index(Request $request): Response
    {
        $status = (string) $request->query('status', 'All');
        $allowedStatuses = ['All', 'Draft', 'Published', 'Archived'];
        $statusFilter = in_array($status, $allowedStatuses, true) ? $status : 'All';
        $audience = (string) $request->query('audience_type', 'All');
        $allowedAudiences = ['All', 'all', 'barangay', 'group'];
        $audienceFilter = in_array($audience, $allowedAudiences, true) ? $audience : 'All';

        $advisories = Advisory::query()
            ->select([
                'id',
                'title',
                'slug',
                'status',
                'audience_type',
                'barangay_id',
                'member_type_id',
                'published_by',
                'published_at',
                'created_at',
            ])
            ->with([
                'publisher:id,name',
                'barangay:id,name',
                'memberType:id,code,name',
            ])
            ->withCount('attachments')
            ->when($statusFilter !== 'All', fn (Builder $query) => $query->where('status', $statusFilter))
            ->when($audienceFilter !== 'All', fn (Builder $query) => $query->where('audience_type', $audienceFilter))
            ->orderByRaw("CASE WHEN published_at IS NULL THEN 1 ELSE 0 END")
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Advisory $advisory): array => $this->serializeListItem($advisory));

        $summary = Advisory::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'Draft' THEN 1 ELSE 0 END) as drafts")
            ->selectRaw("SUM(CASE WHEN status = 'Published' THEN 1 ELSE 0 END) as published")
            ->selectRaw("SUM(CASE WHEN status = 'Archived' THEN 1 ELSE 0 END) as archived")
            ->first();

        return Inertia::render('Admin/Advisories/Index', [
            'advisories' => $advisories,
            'filters' => [
                'status' => $statusFilter,
                'audience_type' => $audienceFilter,
            ],
            'statusOptions' => [
                ['value' => 'All', 'label' => 'All statuses'],
                ['value' => 'Draft', 'label' => 'Draft'],
                ['value' => 'Published', 'label' => 'Published'],
                ['value' => 'Archived', 'label' => 'Archived'],
            ],
            'audienceOptions' => [
                ['value' => 'All', 'label' => 'Global (All Farmers)'],
                ['value' => 'all', 'label' => 'All Farmers'],
                ['value' => 'barangay', 'label' => 'Barangay Audience'],
                ['value' => 'group', 'label' => 'Member Type Audience'],
            ],
            'summary' => [
                'total' => (int) ($summary?->total ?? 0),
                'drafts' => (int) ($summary?->drafts ?? 0),
                'published' => (int) ($summary?->published ?? 0),
                'archived' => (int) ($summary?->archived ?? 0),
            ],
            'urls' => [
                'index' => route('admin.advisories.index'),
                'create' => route('admin.advisories.create'),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Advisories/Create', [
            'advisory' => $this->formDefaults(),
            'reference' => $this->formReferenceData(),
            'urls' => [
                'index' => route('admin.advisories.index'),
                'store' => route('admin.advisories.store'),
            ],
        ]);
    }

    public function store(StoreAdvisoryRequest $request): RedirectResponse
    {
        $payload = $this->normalizePayload($request->validated());

        $advisory = DB::transaction(function () use ($payload, $request): Advisory {
            $advisory = Advisory::query()->create([
                'title' => $payload['title'],
                'slug' => $this->uniqueSlug($payload['title']),
                'content' => $payload['content'],
                'status' => 'Draft',
                'audience_type' => $payload['audience_type'],
                'barangay_id' => $payload['barangay_id'],
                'member_type_id' => $payload['member_type_id'],
                'published_at' => null,
                'published_by' => null,
            ]);

            $this->storeAttachments($advisory, $request->file('attachments', []));

            return $advisory;
        });

        $this->auditTrailService->record(
            'advisories',
            'advisory_created',
            'Created an advisory draft.',
            $request->user(),
            $advisory,
            [
                'title' => $advisory->title,
                'status' => $advisory->status,
                'audience_type' => $advisory->audience_type,
            ],
        );

        return redirect()
            ->route('admin.advisories.show', $advisory)
            ->with('success', 'Advisory draft created.');
    }

    public function show(Advisory $advisory): Response
    {
        $advisory->load([
            'publisher:id,name',
            'barangay:id,name',
            'memberType:id,code,name',
            'attachments',
        ]);

        return Inertia::render('Admin/Advisories/Show', [
            'advisory' => $this->serializeDetail($advisory),
            'urls' => [
                'index' => route('admin.advisories.index'),
                'edit' => route('admin.advisories.edit', $advisory),
            ],
        ]);
    }

    public function edit(Advisory $advisory): Response
    {
        $advisory->load(['barangay:id,name', 'memberType:id,code,name', 'attachments']);

        return Inertia::render('Admin/Advisories/Edit', [
            'advisory' => $this->serializeFormRecord($advisory),
            'reference' => $this->formReferenceData(),
            'urls' => [
                'index' => route('admin.advisories.index'),
                'show' => route('admin.advisories.show', $advisory),
                'update' => route('admin.advisories.update', $advisory),
            ],
        ]);
    }

    public function update(UpdateAdvisoryRequest $request, Advisory $advisory): RedirectResponse
    {
        $payload = $this->normalizePayload($request->validated());
        $before = [
            'title' => $advisory->title,
            'status' => $advisory->status,
            'audience_type' => $advisory->audience_type,
            'barangay_id' => $advisory->barangay_id,
            'member_type_id' => $advisory->member_type_id,
        ];

        DB::transaction(function () use ($advisory, $payload, $request): void {
            $advisory->fill([
                'title' => $payload['title'],
                'slug' => $this->uniqueSlug($payload['title'], $advisory->id),
                'content' => $payload['content'],
                'audience_type' => $payload['audience_type'],
                'barangay_id' => $payload['barangay_id'],
                'member_type_id' => $payload['member_type_id'],
            ])->save();

            $this->storeAttachments($advisory, $request->file('attachments', []));
        });

        $fresh = $advisory->fresh();

        $this->auditTrailService->recordChange(
            'advisories',
            'advisory_updated',
            'Updated an advisory.',
            $request->user(),
            $fresh,
            $before,
            [
                'title' => $fresh?->title,
                'status' => $fresh?->status,
                'audience_type' => $fresh?->audience_type,
                'barangay_id' => $fresh?->barangay_id,
                'member_type_id' => $fresh?->member_type_id,
            ],
        );

        return redirect()
            ->route('admin.advisories.show', $advisory)
            ->with('success', 'Advisory updated successfully.');
    }

    public function publish(Advisory $advisory): RedirectResponse
    {
        DB::transaction(function () use ($advisory): void {
            $payload = $this->publicationService->publish($advisory, Auth::id());

            $advisory->fill([
                'status' => $payload['status'],
                'published_at' => $payload['published_at'],
                'published_by' => $payload['published_by'],
            ])->save();

            $this->queuePublishedNotification($advisory->fresh(['barangay:id,name', 'memberType:id,code,name']));
        });

        $fresh = $advisory->fresh();

        $this->auditTrailService->record(
            'advisories',
            'advisory_published',
            'Published an advisory.',
            Auth::user(),
            $fresh,
            [
                'title' => $fresh?->title,
                'status' => $fresh?->status,
            ],
        );

        $this->analyticsService->track('advisory_published', [
            'module' => 'advisories',
            'properties' => [
                'advisory_id' => $fresh?->id,
                'audience_type' => $fresh?->audience_type,
                'status' => $fresh?->status,
            ],
        ]);

        return redirect()
            ->route('admin.advisories.show', $advisory)
            ->with('success', 'Advisory published successfully.');
    }

    public function unpublish(Advisory $advisory): RedirectResponse
    {
        $before = [
            'status' => $advisory->status,
            'published_at' => optional($advisory->published_at)?->toDateTimeString(),
        ];
        $payload = $this->publicationService->unpublish($advisory);

        $advisory->fill([
            'status' => $payload['status'],
            'published_at' => $payload['published_at'],
        ])->save();

        $this->auditTrailService->recordChange(
            'advisories',
            'advisory_unpublished',
            'Moved an advisory back to draft.',
            Auth::user(),
            $advisory,
            $before,
            [
                'status' => $advisory->status,
                'published_at' => optional($advisory->published_at)?->toDateTimeString(),
            ],
        );

        return redirect()
            ->route('admin.advisories.show', $advisory)
            ->with('success', 'Advisory moved back to draft.');
    }

    public function viewAttachment(Advisory $advisory, Attachment $attachment): StreamedResponse
    {
        $this->ensureAttachmentBelongsToAdvisory($advisory, $attachment);

        /** @var FilesystemAdapter $filesystem */
        $filesystem = Storage::disk('public');
        $filename = $attachment->original_name ?: basename((string) $attachment->file_path);

        return $filesystem->response(
            (string) $attachment->file_path,
            $filename,
            [],
            'inline',
        );
    }

    public function destroyAttachment(Advisory $advisory, Attachment $attachment): RedirectResponse
    {
        $this->ensureAttachmentBelongsToAdvisory($advisory, $attachment);

        DB::transaction(function () use ($attachment): void {
            /** @var FilesystemAdapter $filesystem */
            $filesystem = Storage::disk('public');

            if ($attachment->file_path !== '' && $filesystem->exists((string) $attachment->file_path)) {
                $filesystem->delete((string) $attachment->file_path);
            }

            $attachment->delete();
        });

        $this->auditTrailService->record(
            'advisories',
            'advisory_attachment_removed',
            'Removed an advisory attachment.',
            Auth::user(),
            $advisory,
            [
                'attachment_name' => $attachment->original_name,
            ],
        );

        return back()->with('success', 'Attachment removed from advisory.');
    }

    private function ensureAttachmentBelongsToAdvisory(Advisory $advisory, Attachment $attachment): void
    {
        if ((int) $attachment->module_id !== (int) $advisory->id || $attachment->module_type !== $advisory->getMorphClass()) {
            abort(404);
        }
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title);
        $slug = $base !== '' ? $base : 'advisory';
        $counter = 1;

        while (Advisory::query()
            ->when($ignoreId !== null, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = ($base !== '' ? $base : 'advisory') . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * @param array<int, UploadedFile>|UploadedFile|null $files
     */
    private function storeAttachments(Advisory $advisory, array|UploadedFile|null $files): void
    {
        $uploads = $files instanceof UploadedFile ? [$files] : array_filter($files ?? []);

        foreach ($uploads as $file) {
            $path = $file->store('advisories/' . $advisory->id, 'public');

            $advisory->attachments()->create([
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'file_path' => $path,
                'uploaded_at' => now(),
            ]);
        }
    }

    private function queuePublishedNotification(Advisory $advisory): void
    {
        $recipients = $this->targetedFarmerQuery($advisory)
            ->select(['id'])
            ->with('profile:id,farmer_id,mobile_number')
            ->get()
            ->map(function (Farmer $farmer): array {
                return [
                    'farmer_id' => $farmer->id,
                    'recipient_address' => $farmer->profile?->mobile_number,
                ];
            })
            ->all();

        if ($recipients === []) {
            return;
        }

        $this->notificationDispatchService->persist(
            NotificationType::ADVISORY_PUBLISHED,
            $recipients,
            [
                'subject' => 'New Advisory Published',
                'message' => $advisory->title,
                'advisory_id' => $advisory->id,
                'slug' => $advisory->slug,
                'audience_type' => $advisory->audience_type,
                'published_at' => optional($advisory->published_at)?->toDateTimeString(),
            ],
            Auth::id(),
        );
    }

    private function targetedFarmerQuery(Advisory $advisory): Builder
    {
        return Farmer::query()
            ->when($advisory->audience_type === 'barangay' && $advisory->barangay_id, fn (Builder $query) => $query->where('barangay_id', $advisory->barangay_id))
            ->when($advisory->audience_type === 'group' && $advisory->member_type_id, fn (Builder $query) => $query->where('member_type_id', $advisory->member_type_id));
    }

    private function normalizePayload(array $validated): array
    {
        $audienceType = (string) ($validated['audience_type'] ?? 'all');

        return [
            'title' => (string) $validated['title'],
            'content' => (string) $validated['content'],
            'audience_type' => $audienceType,
            'barangay_id' => $audienceType === 'barangay' ? ($validated['barangay_id'] ?? null) : null,
            'member_type_id' => $audienceType === 'group' ? ($validated['member_type_id'] ?? null) : null,
        ];
    }

    private function formReferenceData(): array
    {
        return [
            'audienceTypes' => [
                ['value' => 'all', 'label' => 'All farmers'],
                ['value' => 'barangay', 'label' => 'Specific barangay'],
                ['value' => 'group', 'label' => 'Specific member type'],
            ],
            'barangays' => Barangay::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Barangay $barangay): array => [
                    'id' => $barangay->id,
                    'name' => $barangay->name,
                ])
                ->all(),
            'memberTypes' => MemberType::query()
                ->orderBy('code')
                ->get(['id', 'code', 'name'])
                ->map(fn (MemberType $memberType): array => [
                    'id' => $memberType->id,
                    'label' => trim($memberType->code . ' - ' . $memberType->name, ' -'),
                ])
                ->all(),
        ];
    }

    private function formDefaults(): array
    {
        return [
            'title' => '',
            'content' => '',
            'audience_type' => 'all',
            'barangay_id' => '',
            'member_type_id' => '',
            'attachments' => [],
            'existingAttachments' => [],
        ];
    }

    private function serializeListItem(Advisory $advisory): array
    {
        return [
            'id' => $advisory->id,
            'title' => $advisory->title,
            'status' => $advisory->status,
            'audienceLabel' => $this->audienceLabel($advisory),
            'attachmentsCount' => (int) $advisory->attachments_count,
            'publishedAt' => optional($advisory->published_at)?->format('M d, Y h:i A'),
            'createdAt' => optional($advisory->created_at)?->format('M d, Y h:i A'),
            'publisher' => $advisory->publisher?->name,
            'actions' => [
                'show' => route('admin.advisories.show', $advisory),
                'edit' => route('admin.advisories.edit', $advisory),
                'publish' => $advisory->status === 'Draft' ? route('admin.advisories.publish', $advisory) : null,
                'unpublish' => $advisory->status === 'Published' ? route('admin.advisories.unpublish', $advisory) : null,
            ],
        ];
    }

    private function serializeDetail(Advisory $advisory): array
    {
        return [
            'id' => $advisory->id,
            'title' => $advisory->title,
            'content' => $advisory->content,
            'status' => $advisory->status,
            'audienceLabel' => $this->audienceLabel($advisory),
            'publishedAt' => optional($advisory->published_at)?->format('M d, Y h:i A'),
            'createdAt' => optional($advisory->created_at)?->format('M d, Y h:i A'),
            'publisher' => $advisory->publisher?->name,
            'attachments' => $this->serializeAttachments($advisory, $advisory->attachments),
            'actions' => [
                'edit' => route('admin.advisories.edit', $advisory),
                'publish' => $advisory->status === 'Draft' ? route('admin.advisories.publish', $advisory) : null,
                'unpublish' => $advisory->status === 'Published' ? route('admin.advisories.unpublish', $advisory) : null,
            ],
        ];
    }

    private function serializeFormRecord(Advisory $advisory): array
    {
        return [
            'title' => $advisory->title,
            'content' => $advisory->content,
            'audience_type' => $advisory->audience_type ?: 'all',
            'barangay_id' => $advisory->barangay_id ? (string) $advisory->barangay_id : '',
            'member_type_id' => $advisory->member_type_id ? (string) $advisory->member_type_id : '',
            'existingAttachments' => $this->serializeAttachments($advisory, $advisory->attachments),
        ];
    }

    /**
     * @param Collection<int, Attachment> $attachments
     * @return array<int, array<string, mixed>>
     */
    private function serializeAttachments(Advisory $advisory, Collection $attachments): array
    {
        return $attachments
            ->map(fn (Attachment $attachment): array => [
                'id' => $attachment->id,
                'name' => $attachment->original_name ?: basename((string) $attachment->file_path),
                'uploadedAt' => optional($attachment->uploaded_at)?->format('M d, Y h:i A'),
                'downloadUrl' => route('admin.advisories.attachments.show', ['advisory' => $advisory, 'attachment' => $attachment]),
                'deleteUrl' => route('admin.advisories.attachments.destroy', ['advisory' => $advisory, 'attachment' => $attachment]),
            ])
            ->values()
            ->all();
    }

    private function audienceLabel(Advisory $advisory): string
    {
        return match ($advisory->audience_type) {
            'barangay' => 'Barangay: ' . ($advisory->barangay?->name ?? 'Not set'),
            'group' => 'Member type: ' . ($advisory->memberType ? trim($advisory->memberType->code . ' - ' . $advisory->memberType->name, ' -') : 'Not set'),
            default => 'All farmers',
        };
    }
}
