<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DocumentType as DocumentTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\DocumentRequirement;
use App\Models\DocumentType;
use App\Models\User;
use App\Services\Audit\AuditTrailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class DocumentRequirementController extends Controller
{
    public function __construct()
    {
        $this->middleware('role.in:' . User::ROLE_ADMIN);
    }

    public function index(Request $request): InertiaResponse
    {
        $selectedTransactionType = $this->normalizeTransactionType((string) $request->query('transaction_type', 'Application'));
        $documentTypes = $this->resolveDocumentTypes();

        $requirements = DocumentRequirement::query()
            ->with('documentType:id,code,name,status')
            ->orderByRaw($this->transactionTypeOrderSql())
            ->orderBy('id')
            ->get();

        return Inertia::render('Admin/DocumentRequirements/Index', [
            'filters' => [
                'transaction_type' => $selectedTransactionType,
            ],
            'transactionTypeOptions' => $this->transactionTypeOptions(),
            'documentTypeOptions' => $documentTypes
                ->map(fn (DocumentType $type): array => [
                    'id' => $type->id,
                    'code' => $type->code,
                    'label' => $type->name ?: $type->code,
                ])
                ->values()
                ->all(),
            'summary' => [
                'total' => $requirements->count(),
                'active' => $requirements->where('is_active', true)->count(),
                'required' => $requirements->where('is_required', true)->count(),
                'transactionCounts' => collect($this->transactionTypeOptions())
                    ->map(fn (array $option): array => [
                        'value' => $option['value'],
                        'label' => $option['label'],
                        'count' => $requirements->where('transaction_type', $option['value'])->count(),
                    ])
                    ->values()
                    ->all(),
            ],
            'requirements' => $requirements
                ->filter(fn (DocumentRequirement $requirement): bool => $selectedTransactionType === 'All'
                    ? true
                    : $requirement->transaction_type === $selectedTransactionType)
                ->values()
                ->map(fn (DocumentRequirement $requirement): array => $this->serializeRequirement($requirement))
                ->all(),
            'formDefaults' => [
                'transaction_type' => $selectedTransactionType === 'All' ? 'Application' : $selectedTransactionType,
                'document_type_id' => '',
                'is_required' => true,
                'is_active' => true,
            ],
            'urls' => [
                'index' => route('admin.document-requirements.index'),
                'store' => route('admin.document-requirements.store'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateRequirement($request);

        $requirement = DocumentRequirement::query()->create($validated);

        app(AuditTrailService::class)->record(
            'membership_requirements',
            'requirement_added',
            'Added a document requirement.',
            Auth::user(),
            $requirement,
            $validated
        );

        return redirect()
            ->route('admin.document-requirements.index', [
                'transaction_type' => $validated['transaction_type'],
            ])
            ->with('success', 'Document requirement added.');
    }

    public function update(Request $request, DocumentRequirement $documentRequirement): RedirectResponse
    {
        $before = [
            'transaction_type' => $documentRequirement->transaction_type,
            'document_type_id' => $documentRequirement->document_type_id,
            'is_required' => $documentRequirement->is_required,
            'is_active' => $documentRequirement->is_active,
        ];

        $validated = $this->validateRequirement($request, $documentRequirement);

        $documentRequirement->update($validated);

        app(AuditTrailService::class)->recordChange(
            'membership_requirements',
            'requirement_updated',
            'Updated a document requirement.',
            Auth::user(),
            $documentRequirement,
            $before,
            $validated
        );

        return redirect()
            ->route('admin.document-requirements.index', [
                'transaction_type' => $this->normalizeTransactionType((string) $request->input('transaction_type_filter', $validated['transaction_type'])),
            ])
            ->with('success', 'Document requirement updated.');
    }

    public function destroy(Request $request, DocumentRequirement $documentRequirement): RedirectResponse
    {
        $transactionType = $documentRequirement->transaction_type;
        $documentTypeId = $documentRequirement->document_type_id;

        $documentRequirement->delete();

        app(AuditTrailService::class)->record(
            'membership_requirements',
            'requirement_deleted',
            'Deleted a document requirement.',
            Auth::user(),
            null,
            [
                'transaction_type' => $transactionType,
                'document_type_id' => $documentTypeId,
            ]
        );

        return redirect()
            ->route('admin.document-requirements.index', [
                'transaction_type' => $this->normalizeTransactionType((string) $request->input('transaction_type_filter', $transactionType)),
            ])
            ->with('success', 'Document requirement deleted.');
    }

    private function validateRequirement(Request $request, ?DocumentRequirement $documentRequirement = null): array
    {
        $documentTypeId = $request->input('document_type_id');
        $creatingDocumentType = blank($documentTypeId);

        $validated = $request->validate([
            'transaction_type' => ['required', Rule::in($this->transactionTypeValues())],
            'document_type_id' => [
                Rule::requiredIf(! $creatingDocumentType),
                'nullable',
                'integer',
                Rule::exists('document_types', 'id'),
                Rule::unique('document_requirements', 'document_type_id')
                    ->ignore($documentRequirement?->id)
                    ->where(fn ($query) => $query->where('transaction_type', $request->input('transaction_type'))),
            ],
            'new_document_type_name' => [
                Rule::requiredIf($creatingDocumentType),
                'nullable',
                'string',
                'max:255',
                Rule::unique('document_types', 'name'),
            ],
            'is_required' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
        ]);

        $validated['transaction_type'] = $this->normalizeTransactionType($validated['transaction_type']);
        $validated['is_required'] = (bool) $validated['is_required'];
        $validated['is_active'] = (bool) $validated['is_active'];

        if ($creatingDocumentType) {
            $documentName = trim((string) $validated['new_document_type_name']);
            $documentType = DocumentType::query()->create([
                'code' => $this->generateDocumentTypeCode($documentName),
                'name' => $documentName,
                'description' => $documentName,
                'status' => 'Active',
            ]);

            $validated['document_type_id'] = $documentType->id;
        }

        unset($validated['new_document_type_name']);

        return $validated;
    }

    private function resolveDocumentTypes()
    {
        foreach (DocumentTypeEnum::cases() as $type) {
            DocumentType::query()->firstOrCreate(
                ['code' => $type->value],
                [
                    'name' => $type->label(),
                    'description' => $type->label(),
                    'status' => 'Active',
                ]
            );
        }

        return DocumentType::query()
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'status']);
    }

    private function serializeRequirement(DocumentRequirement $requirement): array
    {
        return [
            'id' => $requirement->id,
            'transactionType' => $requirement->transaction_type,
            'documentType' => [
                'id' => $requirement->documentType?->id,
                'code' => $requirement->documentType?->code,
                'label' => $requirement->documentType?->name ?: $requirement->documentType?->code ?: 'Unknown document',
            ],
            'isRequired' => (bool) $requirement->is_required,
            'isActive' => (bool) $requirement->is_active,
            'actions' => [
                'updateUrl' => route('admin.document-requirements.update', $requirement),
                'deleteUrl' => route('admin.document-requirements.destroy', $requirement),
            ],
        ];
    }

    private function transactionTypeOptions(): array
    {
        return [
            ['value' => 'All', 'label' => 'All Transactions'],
            ['value' => 'Mortuary', 'label' => 'Mortuary'],
            ['value' => 'Application', 'label' => 'Application'],
            ['value' => 'Renewal', 'label' => 'Renewal'],
            ['value' => 'Reactivation', 'label' => 'Reactivation'],
        ];
    }

    private function transactionTypeValues(): array
    {
        return ['Mortuary', 'Application', 'Renewal', 'Reactivation'];
    }

    private function normalizeTransactionType(string $transactionType): string
    {
        $normalized = ucfirst(strtolower(trim($transactionType)));

        return in_array($normalized, $this->transactionTypeValues(), true)
            ? $normalized
            : ($normalized === 'All' ? 'All' : 'Application');
    }

    private function transactionTypeOrderSql(): string
    {
        return "case transaction_type when 'Mortuary' then 1 when 'Application' then 2 when 'Renewal' then 3 when 'Reactivation' then 4 else 5 end";
    }

    private function generateDocumentTypeCode(string $documentName): string
    {
        $baseCode = Str::lower(Str::snake($documentName));
        $baseCode = $baseCode !== '' ? $baseCode : 'document_type';
        $code = $baseCode;
        $suffix = 2;

        while (DocumentType::query()->where('code', $code)->exists()) {
            $code = $baseCode . '_' . $suffix;
            $suffix++;
        }

        return $code;
    }
}
