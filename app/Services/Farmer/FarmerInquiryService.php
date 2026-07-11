<?php

namespace App\Services\Farmer;

use App\Enums\NotificationType;
use App\Models\Attachment;
use App\Models\Farmer;
use App\Models\Query;
use App\Models\QueryCategory;
use App\Models\QueryResponse;
use App\Models\User;
use Carbon\CarbonInterface;
use App\Services\Notifications\NotificationDispatchService;
use App\Services\Queries\QueryWorkflowService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FarmerInquiryService
{
    public function __construct(
        private readonly QueryWorkflowService $queryWorkflowService,
        private readonly NotificationDispatchService $notificationDispatchService,
        private readonly FarmerNotificationService $notificationService,
    ) {
    }

    public function list(Farmer $farmer, bool $archived = false, int $perPage = 10, ?string $status = null): LengthAwarePaginator
    {
        $paginator = Query::query()
            ->where('farmer_id', $farmer->id)
            ->when(
                $archived,
                fn (Builder $query) => $query->whereNotNull('archived_at'),
                fn (Builder $query) => $query->whereNull('archived_at'),
            )
            ->when($status, fn (Builder $query, string $statusValue) => $query->where('status', $statusValue))
            ->with(['category'])
            ->withCount('responses')
            ->latest('created_at')
            ->latest('id')
            ->paginate($perPage);

        $queryIds = $paginator->getCollection()->pluck('id')->all();
        $replyMeta = $this->replyMetadata($queryIds);
        $unreadCounts = $this->notificationService->unreadQueryReplyCounts($farmer);

        $paginator->getCollection()->transform(function (Query $query) use ($replyMeta, $unreadCounts): Query {
            $meta = $replyMeta[$query->id] ?? [];
            $query->setAttribute('last_activity_at', $meta['last_activity_at'] ?? $query->created_at);
            $query->setAttribute('last_staff_reply_at', $meta['last_staff_reply_at'] ?? null);
            $query->setAttribute('last_staff_reply_excerpt', $meta['last_staff_reply_excerpt'] ?? null);
            $query->setAttribute('unread_reply_count', $unreadCounts[$query->id] ?? 0);
            $query->setAttribute('has_unread_reply', ($unreadCounts[$query->id] ?? 0) > 0);

            return $query;
        });

        return $paginator;
    }

    public function summary(Farmer $farmer): array
    {
        return [
            'total' => Query::query()->where('farmer_id', $farmer->id)->count(),
            'new' => Query::query()->where('farmer_id', $farmer->id)->whereNull('archived_at')->where('status', 'New')->count(),
            'in_progress' => Query::query()->where('farmer_id', $farmer->id)->whereNull('archived_at')->whereIn('status', ['In Progress', 'Escalated'])->count(),
            'resolved' => Query::query()->where('farmer_id', $farmer->id)->whereNull('archived_at')->where('status', 'Resolved')->count(),
            'open' => Query::query()->where('farmer_id', $farmer->id)->whereNull('archived_at')->whereIn('status', ['New', 'In Progress', 'Escalated'])->count(),
            'archived' => Query::query()->where('farmer_id', $farmer->id)->whereNotNull('archived_at')->count(),
        ];
    }

    public function categories(): array
    {
        $this->ensureDefaultCategories();

        return QueryCategory::query()
            ->where('status', 'Active')
            ->orderBy('name')
            ->get(['id', 'code', 'name'])
            ->map(fn (QueryCategory $category) => [
                'id' => $category->id,
                'code' => $category->code,
                'name' => $category->name,
            ])
            ->all();
    }

    public function templates(): array
    {
        $categories = collect($this->categories())->keyBy('code');

        return [
            [
                'key' => 'renewal_help',
                'label' => 'I need help with renewal',
                'subject' => 'Need help with my renewal',
                'message' => 'I need help with my renewal requirements or current renewal status.',
                'category_code' => 'RENEWAL',
                'category_id' => $categories->get('RENEWAL')['id'] ?? null,
            ],
            [
                'key' => 'payment_not_reflected',
                'label' => 'My payment is not reflected',
                'subject' => 'My payment is not reflected',
                'message' => 'I already submitted or paid my amount due, but it is not yet reflected in my account.',
                'category_code' => 'PAYMENT',
                'category_id' => $categories->get('PAYMENT')['id'] ?? null,
            ],
            [
                'key' => 'cannot_login',
                'label' => 'I cannot log in',
                'subject' => 'I cannot log in to my account',
                'message' => 'I am having trouble logging in to my AniTech farmer account and need assistance.',
                'category_code' => 'ACCOUNT',
                'category_id' => $categories->get('ACCOUNT')['id'] ?? null,
            ],
            [
                'key' => 'missing_documents',
                'label' => 'What documents are missing?',
                'subject' => 'What documents are missing from my record?',
                'message' => 'Please let me know which documents are still missing or need to be replaced in my record.',
                'category_code' => 'DOCUMENTS',
                'category_id' => $categories->get('DOCUMENTS')['id'] ?? null,
            ],
        ];
    }

    public function create(Farmer $farmer, ?User $user, array $validated, array $files = []): Query
    {
        $payload = $this->queryWorkflowService->submit($validated);
        $this->ensureDefaultCategories();

        return DB::transaction(function () use ($farmer, $payload, $files): Query {
            $query = Query::query()->create([
                'farmer_id' => $farmer->id,
                'category_id' => $this->resolveCategoryId($payload),
                'subject' => $payload['subject'],
                'message' => $payload['message'],
                'status' => $payload['status'],
                'created_at' => $payload['created_at'],
                'archived_at' => null,
            ]);

            $this->storeAttachments($query, $files, 'images');
            $this->notifyAdmins($query, $farmer, false);

            return $query->load(['category', 'attachments'])->loadCount('responses');
        });
    }

    public function thread(Farmer $farmer, int $inquiryId): Query
    {
        $query = Query::query()
            ->where('farmer_id', $farmer->id)
            ->with([
                'farmer',
                'category',
                'attachments',
                'responses.attachments',
                'responses.responder:id,name,role,farmer_id',
            ])
            ->withCount('responses')
            ->findOrFail($inquiryId);

        $unreadCount = $this->notificationService->unreadQueryReplyCounts($farmer)[$query->id] ?? 0;
        $this->notificationService->markQueryRepliesAsRead($farmer, $query);

        $query->responses->transform(function (QueryResponse $response) use ($query): QueryResponse {
            $response->setRelation('inquiry', $query);
            $response->setAttribute('is_from_staff', $this->isStaffResponse($response, $query->farmer_id));

            return $response;
        });

        $latestResponse = $query->responses
            ->sortByDesc(fn (QueryResponse $response) => $response->responded_at?->getTimestamp() ?? 0)
            ->first();

        $latestStaffResponse = $query->responses
            ->filter(fn (QueryResponse $response) => $this->isStaffResponse($response, $query->farmer_id))
            ->sortByDesc(fn (QueryResponse $response) => $response->responded_at?->getTimestamp() ?? 0)
            ->first();

        $query->setAttribute('last_activity_at', $latestResponse?->responded_at ?? $query->created_at);
        $query->setAttribute('last_staff_reply_at', $latestStaffResponse?->responded_at);
        $query->setAttribute('last_staff_reply_excerpt', $latestStaffResponse ? Str::limit($latestStaffResponse->message, 140) : null);
        $query->setAttribute('unread_staff_reply_count', $unreadCount);
        $query->setAttribute('unread_reply_count', $unreadCount);
        $query->setAttribute('has_unread_reply', $unreadCount > 0);

        return $query;
    }

    public function reply(Farmer $farmer, User $user, int $inquiryId, array $validated, array $files = []): Query
    {
        $query = Query::query()
            ->where('farmer_id', $farmer->id)
            ->findOrFail($inquiryId);

        $payload = $this->queryWorkflowService->respond($query, $validated);

        DB::transaction(function () use ($query, $user, $payload, $files, $farmer): void {
            $query->forceFill([
                'status' => $payload['query']['status'],
                'archived_at' => null,
            ])->save();

            $response = $query->responses()->create([
                'responded_by' => $user->id,
                'message' => $payload['response']['message'] ?? '',
                'responded_at' => $payload['response']['responded_at'],
            ]);

            $this->storeAttachments($response, $files, 'attachments');
            $this->notifyAdmins($query, $farmer, true);
        });

        return $this->thread($farmer, $query->id);
    }

    private function resolveCategoryId(array $payload): int
    {
        if (! empty($payload['category_id'])) {
            return (int) $payload['category_id'];
        }

        return (int) QueryCategory::query()
            ->where('status', 'Active')
            ->orderBy('name')
            ->value('id');
    }

    private function ensureDefaultCategories(): void
    {
        foreach ($this->defaultCategories() as $category) {
            QueryCategory::query()->updateOrCreate(
                ['code' => $category['code']],
                [
                    'name' => $category['name'],
                    'description' => $category['description'],
                    'status' => 'Active',
                ],
            );
        }
    }

    private function defaultCategories(): array
    {
        return [
            ['code' => 'ACCOUNT', 'name' => 'Account', 'description' => 'Login, password, and account access concerns.'],
            ['code' => 'DOCUMENTS', 'name' => 'Documents', 'description' => 'Missing, invalid, or replacement document concerns.'],
            ['code' => 'MEMBERSHIP', 'name' => 'Membership', 'description' => 'Membership record, profile, and general membership concerns.'],
            ['code' => 'OTHER', 'name' => 'Other', 'description' => 'Other support requests not covered by the main inquiry topics.'],
            ['code' => 'PAYMENT', 'name' => 'Payment', 'description' => 'Payment status, proof, verification, and assessment concerns.'],
            ['code' => 'RENEWAL', 'name' => 'Renewal', 'description' => 'Renewal status, requirements, and renewal workflow concerns.'],
        ];
    }

    private function replyMetadata(array $queryIds): array
    {
        if ($queryIds === []) {
            return [];
        }

        $farmerIdsByQueryId = Query::query()
            ->whereIn('id', $queryIds)
            ->pluck('farmer_id', 'id');

        $responses = QueryResponse::query()
            ->with('responder:id,role,farmer_id')
            ->whereIn('query_id', $queryIds)
            ->orderByDesc('responded_at')
            ->get(['id', 'query_id', 'responded_by', 'message', 'responded_at']);

        return collect($queryIds)->mapWithKeys(function (int $queryId) use ($responses, $farmerIdsByQueryId): array {
            $queryResponses = $responses->where('query_id', $queryId);
            $latestResponse = $queryResponses->sortByDesc(fn (QueryResponse $response) => $response->responded_at?->getTimestamp() ?? 0)->first();
            $latestStaffResponse = $queryResponses
                ->filter(fn (QueryResponse $response) => $this->isStaffResponse($response, $farmerIdsByQueryId[$queryId] ?? null))
                ->sortByDesc(fn (QueryResponse $response) => $response->responded_at?->getTimestamp() ?? 0)
                ->first();

            return [
                $queryId => [
                    'last_activity_at' => $latestResponse?->responded_at,
                    'last_staff_reply_at' => $latestStaffResponse?->responded_at,
                    'last_staff_reply_excerpt' => $latestStaffResponse ? Str::limit($latestStaffResponse->message, 120) : null,
                ],
            ];
        })->all();
    }

    private function notifyAdmins(Query $query, Farmer $farmer, bool $isReply): void
    {
        $recipients = User::query()
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_STAFF])
            ->get(['id', 'email'])
            ->map(fn (User $user): array => [
                'user_id' => $user->id,
                'email' => $user->email,
            ])
            ->all();

        if ($recipients === []) {
            return;
        }

        $farmerName = $farmer->full_name ?: 'A farmer';

        $this->notificationDispatchService->persist(
            NotificationType::QUERY_RECEIVED,
            $recipients,
            [
                'subject' => $isReply ? 'Farmer replied to an inquiry' : 'New farmer query received',
                'message' => $isReply
                    ? $farmerName . ' replied to query: ' . $query->subject . '.'
                    : $farmerName . ' submitted a new query: ' . $query->subject . '.',
                'query_id' => $query->id,
                'farmer_id' => $query->farmer_id,
                'subject_line' => $query->subject,
            ],
        );
    }

    private function storeAttachments(Query|QueryResponse $model, array $files, string $key): void
    {
        $uploads = array_filter($files[$key] ?? []);

        foreach ($uploads as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $path = $file->store('queries/' . $model->getKey() . '/attachments', 'public');

            $model->attachments()->create([
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'file_path' => $path,
                'uploaded_at' => now(),
            ]);
        }
    }

    private function isStaffResponse(QueryResponse $response, int|null $farmerId): bool
    {
        if ((int) ($response->responder?->farmer_id ?? 0) !== 0 && (int) ($response->responder?->farmer_id ?? 0) === (int) ($farmerId ?? 0)) {
            return false;
        }

        return in_array($response->responder?->role, [User::ROLE_ADMIN, User::ROLE_STAFF], true);
    }
}
