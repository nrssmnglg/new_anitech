<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FarmerStatus;
use App\Enums\PaymentStatus;
use App\Exports\MortuaryQueueExport;
use App\Exports\MortuaryRecordsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReviewMortuaryClaimRequest;
use App\Http\Requests\Admin\StoreMortuaryClaimRequest;
use App\Models\Barangay;
use App\Models\ClaimRequirementType;
use App\Models\DocumentRequirement;
use App\Models\Farmer;
use App\Models\MembershipLedger;
use App\Models\MortuaryClaim;
use App\Services\Mortuary\MortuaryClaimService;
use Barryvdh\DomPDF\Facade\Pdf;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MortuaryClaimController extends Controller
{
    public function __construct(
        private readonly MortuaryClaimService $claimService,
    ) {
    }

    public function index(Request $request): InertiaResponse
    {
        $activeSection = $request->query('section') === 'records' ? 'records' : 'queue';
        $filters = $this->filters($request);
        if ($activeSection === 'records') {
            $baseQuery = MortuaryClaim::query()->with($this->claimListRelations());
            $claims = $this->buildRecordsQuery(clone $baseQuery, $filters)
                ->latest('claim_date')
                ->latest('id')
                ->paginate(12, ['*'], 'records_page')
                ->withQueryString();

            $claims->through(fn (MortuaryClaim $claim): array => $this->serializeClaimRow($claim));
        } else {
            $queueQuery = $this->eligibleFarmerQueueQuery($filters);
            $claims = $queueQuery
                ->latest('id')
                ->latest('id')
                ->paginate(12, ['*'], 'queue_page')
                ->withQueryString();

            $claims->through(fn (Farmer $farmer): array => $this->serializeEligibleFarmerRow($farmer));
        }

        $years = MortuaryClaim::query()
            ->selectRaw('YEAR(claim_date) as claim_year')
            ->distinct()
            ->orderByDesc('claim_year')
            ->pluck('claim_year')
            ->filter();
        $barangays = Barangay::query()->orderBy('name')->get(['id', 'name']);
        $queueQuery = $this->eligibleFarmerQueueQuery($filters);
        $recordQuery = MortuaryClaim::query();

        return Inertia::render('Admin/MortuaryClaims/Index', [
            'activeSection' => $activeSection,
            'claims' => $claims,
            'filters' => $filters,
            'filterOptions' => [
                'statuses' => [
                    ['value' => 'pending', 'label' => 'For Review'],
                    ['value' => 'completed', 'label' => 'Completed'],
                    ['value' => 'rejected', 'label' => 'Rejected'],
                ],
                'years' => $years->map(fn ($year): array => ['value' => (string) $year, 'label' => (string) $year])->values()->all(),
                'barangays' => $barangays->map(fn (Barangay $barangay): array => ['id' => $barangay->id, 'name' => $barangay->name])->values()->all(),
            ],
            'summary' => [
                'queueCount' => (clone $queueQuery)->count(),
                'recordsCount' => (clone $recordQuery)->count(),
                'completedCount' => MortuaryClaim::query()->whereIn('status', [
                    $this->databaseStatusValue('approved'),
                    $this->databaseStatusValue('released'),
                ])->count(),
            ],
            'urls' => [
                'queue' => route('admin.mortuary-claims.index'),
                'records' => route('admin.mortuary-claims.index', ['section' => 'records']),
                'report' => route('admin.mortuary-claims.report'),
            ],
            'pageTitle' => $activeSection === 'records' ? 'Mortuary Records' : 'Mortuary Claim Queue',
            'pageSubtitle' => $activeSection === 'records'
                ? 'Search filed mortuary claim records.'
                : '',
        ]);
    }

    public function report(Request $request): View|BinaryFileResponse|Response
    {
        $activeSection = $request->query('section') === 'records' ? 'records' : 'queue';
        $filters = $this->filters($request);
        $format = strtolower((string) $request->query('format', 'html'));
        $selectedBarangay = filled($filters['barangay_id'] ?? null)
            ? Barangay::query()->find($filters['barangay_id'], ['id', 'name'])
            : null;

        if ($activeSection === 'records') {
            $rows = $this->buildRecordExportRows(
                $this->buildRecordsQuery(
                    MortuaryClaim::query()->with($this->claimListRelations()),
                    $filters
                )->latest('claim_date')->latest('id')->get()
            );
            $selectedColumns = $this->selectedRecordColumns($request);

            if ($format === 'xlsx') {
                return Excel::download(
                    new MortuaryRecordsExport($rows, $selectedColumns),
                    'Mortuary-Records-' . now()->format('m-d-Y') . '.xlsx'
                );
            }

            if ($format === 'pdf') {
                $pdf = Pdf::loadView('admin.mortuary-claims.records-report', [
                    'generatedAt' => now(),
                    'filters' => $filters,
                    'selectedBarangay' => $selectedBarangay,
                    'rows' => $rows,
                    'selectedColumns' => collect($selectedColumns)
                        ->mapWithKeys(fn (string $column): array => [$column => MortuaryRecordsExport::availableColumns()[$column]])
                        ->all(),
                ])->setPaper('a4', 'landscape');

                return $pdf->download('Mortuary-Records-' . now()->format('m-d-Y') . '.pdf');
            }

            return view('admin.mortuary-claims.records-report', [
                'generatedAt' => now(),
                'filters' => $filters,
                'selectedBarangay' => $selectedBarangay,
                'rows' => $rows,
                'selectedColumns' => collect($selectedColumns)
                    ->mapWithKeys(fn (string $column): array => [$column => MortuaryRecordsExport::availableColumns()[$column]])
                    ->all(),
            ]);
        }

        $rows = $this->buildQueueExportRows(
            $this->eligibleFarmerQueueQuery($filters)->latest('id')->get()
        );
        $selectedColumns = $this->selectedQueueColumns($request);

        if ($format === 'xlsx') {
            return Excel::download(
                new MortuaryQueueExport($rows, $selectedColumns),
                'Mortuary-Queue-' . now()->format('m-d-Y') . '.xlsx'
            );
        }

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('admin.mortuary-claims.queue-report', [
                'generatedAt' => now(),
                'filters' => $filters,
                'selectedBarangay' => $selectedBarangay,
                'rows' => $rows,
                'selectedColumns' => collect($selectedColumns)
                    ->mapWithKeys(fn (string $column): array => [$column => MortuaryQueueExport::availableColumns()[$column]])
                    ->all(),
            ])->setPaper('a4', 'landscape');

            return $pdf->download('Mortuary-Queue-' . now()->format('m-d-Y') . '.pdf');
        }

        return view('admin.mortuary-claims.queue-report', [
            'generatedAt' => now(),
            'filters' => $filters,
            'selectedBarangay' => $selectedBarangay,
            'rows' => $rows,
            'selectedColumns' => collect($selectedColumns)
                ->mapWithKeys(fn (string $column): array => [$column => MortuaryQueueExport::availableColumns()[$column]])
                ->all(),
        ]);
    }

    private function buildRecordsQuery(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $nested) use ($search): void {
                    $nested
                        ->where('claim_reference', 'like', "%{$search}%")
                        ->orWhereHas('membershipLedger.membershipTransaction.farmer.profile', function (Builder $profileQuery) use ($search): void {
                            $profileQuery
                                ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('middle_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('membershipLedger.membershipTransaction.farmer', function (Builder $farmerQuery) use ($search): void {
                            $farmerQuery
                                ->where('farmer_code', 'like', "%{$search}%");
                        });
                });
            })
            ->when($filters['status'] ?? null, function (Builder $query, string $status): void {
                if ($status === 'completed') {
                    $query->whereIn('status', [
                        $this->databaseStatusValue('approved'),
                        $this->databaseStatusValue('released'),
                    ]);

                    return;
                }

                $query->where('status', $this->databaseStatusValue($status));
            })
            ->when($filters['year'] ?? null, fn (Builder $query, string $year) => $query->whereYear('claim_date', (int) $year))
            ->when($filters['barangay_id'] ?? null, function (Builder $query, string $barangayId): void {
                $query->whereHas('membershipLedger.membershipTransaction.farmer', fn (Builder $farmerQuery) => $farmerQuery->where('barangay_id', $barangayId));
            });
    }

    public function create(Request $request): InertiaResponse
    {
        $eligibleFarmers = $this->eligibleFarmerQueueQuery()
            ->latest('id')
            ->get()
            ->sortBy(fn (Farmer $farmer) => sprintf(
                '%s %s %s',
                $farmer->profile?->last_name ?? '',
                $farmer->profile?->first_name ?? '',
                $farmer->farmer_code ?? ''
            ))
            ->values();
        $selectedLedgerId = (int) $request->integer('membership_ledger_id');
        $selectedFarmer = $eligibleFarmers->first(function (Farmer $farmer) use ($selectedLedgerId): bool {
            return (int) ($this->representativeMortuaryLedger($farmer)?->id) === $selectedLedgerId;
        }) ?: $eligibleFarmers->first();
        $requirements = $this->activeMortuaryRequirements();

        return Inertia::render('Admin/MortuaryClaims/Create', [
            'eligibleLedgers' => $eligibleFarmers->map(fn (Farmer $farmer): array => $this->serializeEligibleFarmerForCreate($farmer))->values()->all(),
            'selectedLedgerId' => $this->representativeMortuaryLedger($selectedFarmer)?->id,
            'requirements' => $requirements->map(fn (DocumentRequirement $requirement): array => [
                'id' => $requirement->id,
                'code' => strtolower((string) $requirement->documentType?->code),
                'label' => $requirement->documentType?->name ?: $requirement->documentType?->code ?: 'Requirement',
            ])->values()->all(),
            'storeUrl' => route('admin.mortuary-claims.store'),
            'queueUrl' => route('admin.mortuary-claims.index'),
        ]);
    }

    public function store(StoreMortuaryClaimRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $ledger = MembershipLedger::query()
            ->with(['membershipTransaction.farmer.profile', 'membershipTransaction.farmer.memberType'])
            ->findOrFail($validated['membership_ledger_id']);

        if (MortuaryClaim::query()
            ->whereHas('membershipLedger.membershipTransaction', fn (Builder $query) => $query->where('farmer_id', $ledger->farmer_id))
            ->where('status', '!=', $this->databaseStatusValue('rejected'))
            ->exists()) {
            throw ValidationException::withMessages([
                'membership_ledger_id' => 'A mortuary claim already exists for the selected farmer.',
            ]);
        }

        [$claimerFirstName, $claimerMiddleName, $claimerLastName] = $this->splitClaimerName((string) $validated['claimer_name']);
        $claimPayload = [
            'membership_ledger_id' => $ledger->id,
            'claim_amount' => $validated['claim_amount'],
            'claim_date' => $validated['claim_date'],
            'claimer_first_name' => $claimerFirstName,
            'claimer_middle_name' => $claimerMiddleName,
            'claimer_last_name' => $claimerLastName,
            'claimer_relationship' => $validated['claimer_relationship'],
            'claimer_contact_number' => $validated['claimer_contact_number'],
            'claimer_address' => $validated['claimer_address'],
            'remarks' => $validated['remarks'] ?? null,
        ];

        try {
            $preparedClaim = $this->claimService->file($claimPayload, $ledger);
        } catch (DomainException $exception) {
            throw ValidationException::withMessages([
                'mortuary' => $exception->getMessage(),
            ]);
        }

        $claim = MortuaryClaim::query()->create([
            'membership_ledger_id' => $preparedClaim['membership_ledger_id'],
            'claim_reference' => $preparedClaim['claim_reference'],
            'claim_amount' => $preparedClaim['claim_amount'],
            'claim_date' => $preparedClaim['claim_date'],
            'claimer_first_name' => $preparedClaim['claimer_first_name'],
            'claimer_middle_name' => $preparedClaim['claimer_middle_name'],
            'claimer_last_name' => $preparedClaim['claimer_last_name'],
            'claimer_relationship' => $preparedClaim['claimer_relationship'],
            'claimer_contact_number' => $preparedClaim['claimer_contact_number'],
            'claimer_address' => $preparedClaim['claimer_address'],
            'status' => $preparedClaim['status'],
            'filed_by' => Auth::id(),
            'remarks' => $preparedClaim['remarks'] ?? null,
        ]);

        $this->syncClaimRequirements($claim, $validated, $this->activeMortuaryRequirements());

        $approvedPayload = $this->claimService->approve($claim, Auth::id());
        $claim->forceFill([
            'status' => $approvedPayload['status'],
            'approved_by' => $approvedPayload['approved_by'] ?? Auth::id(),
            'released_by' => $approvedPayload['released_by'] ?? Auth::id(),
        ])->save();

        if ($claim->farmer) {
            $claim->farmer->forceFill([
                'inactive_at' => now(),
                'inactive_reason' => FarmerStatus::DECEASED->label(),
            ])->save();
        }

        return redirect()
            ->route('admin.mortuary-claims.show', $claim)
            ->with('success', 'Mortuary claim filed and released successfully.');
    }

    public function show(MortuaryClaim $mortuaryClaim): InertiaResponse
    {
        $mortuaryClaim->load([
            'membershipLedger.membershipTransaction.farmer.profile:farmer_id,first_name,middle_name,last_name,suffix',
            'membershipLedger.membershipTransaction.farmer.barangay:id,name',
            'membershipLedger.membershipTransaction.farmer.association:id,name',
            'membershipLedger.membershipTransaction.farmer.memberType:id,code,name',
            'membershipLedger.membershipTransaction.paymentAssessments:id,membership_transaction_id,total_amount_due,mortuary_fee',
            'requirements.requirementType:id,code,name',
            'filedBy:id,name',
            'approvedByUser:id,name',
            'releasedByUser:id,name',
        ]);
        $approvalRequirementsComplete = filled($mortuaryClaim->claimer_name)
            && filled($mortuaryClaim->claimer_relationship)
            && filled($mortuaryClaim->claimer_contact_number)
            && filled($mortuaryClaim->claimer_address)
            && $this->claimRequirementsComplete($mortuaryClaim);

        return Inertia::render('Admin/MortuaryClaims/Show', [
            'claim' => $this->serializeClaimDetail($mortuaryClaim, $approvalRequirementsComplete),
            'permissions' => [
                'canReview' => Auth::user()?->hasRole(\App\Models\User::ROLE_ADMIN) ?? false,
            ],
            'urls' => [
                'queue' => route('admin.mortuary-claims.index'),
                'farmerShow' => $mortuaryClaim->farmer ? route('admin.farmers.show', $mortuaryClaim->farmer) : null,
                'review' => route('admin.mortuary-claims.review', $mortuaryClaim),
            ],
        ]);
    }

    public function viewDeathCertificate(MortuaryClaim $mortuaryClaim): StreamedResponse
    {
        if (! $mortuaryClaim->death_certificate_path) {
            abort(404);
        }

        $disk = $mortuaryClaim->death_certificate_disk ?: 'public';
        $filename = $mortuaryClaim->death_certificate_original_name ?: basename((string) $mortuaryClaim->death_certificate_path);

        return Storage::disk($disk)->response(
            (string) $mortuaryClaim->death_certificate_path,
            $filename,
            ['Content-Type' => $mortuaryClaim->death_certificate_mime_type ?: 'application/octet-stream'],
            'inline',
        );
    }

    public function review(ReviewMortuaryClaimRequest $request, MortuaryClaim $mortuaryClaim): RedirectResponse
    {
        if ($mortuaryClaim->status !== 'pending') {
            throw ValidationException::withMessages([
                'mortuary' => 'Only claims in the review queue can be reviewed.',
            ]);
        }

        if ($request->validated('action') === 'approve') {
            $missingRequirements = collect([
                'claimer_name' => $mortuaryClaim->claimer_name,
                'claimer_relationship' => $mortuaryClaim->claimer_relationship,
                'claimer_contact_number' => $mortuaryClaim->claimer_contact_number,
                'claimer_address' => $mortuaryClaim->claimer_address,
            ])->filter(fn ($value) => blank($value))->keys()->all();

            if ($missingRequirements !== [] || ! $this->claimRequirementsComplete($mortuaryClaim)) {
                throw ValidationException::withMessages([
                    'mortuary' => 'Approval requires the full required-documents checklist and complete claimer details.',
                ]);
            }
        }

        $payload = $request->validated('action') === 'approve'
            ? $this->claimService->approve($mortuaryClaim, Auth::id())
            : $this->claimService->reject($mortuaryClaim, (string) $request->validated('remarks'));

        $mortuaryClaim->fill([
            'status' => $payload['status'],
            'approved_by' => $payload['approved_by'] ?? $mortuaryClaim->approved_by,
            'released_by' => $payload['released_by'] ?? $mortuaryClaim->released_by,
            'remarks' => $payload['remarks'] ?? $mortuaryClaim->remarks,
        ])->save();

        if (in_array($payload['status'] ?? null, ['approved', 'released'], true) && $mortuaryClaim->farmer) {
            $mortuaryClaim->farmer->forceFill([
                'inactive_at' => now(),
                'inactive_reason' => FarmerStatus::DECEASED->label(),
            ])->save();
        }

        if (in_array($payload['status'] ?? null, ['approved', 'released'], true)) {
            return redirect()
                ->route('admin.mortuary-claims.index', ['section' => 'records'])
                ->with('success', 'Mortuary claim approved and released.');
        }

        return redirect()
            ->route('admin.mortuary-claims.show', $mortuaryClaim)
            ->with('success', 'Mortuary claim rejected.');
    }

    private function claimListRelations(): array
    {
        return [
            'membershipLedger.membershipTransaction.farmer.profile:farmer_id,first_name,middle_name,last_name,suffix',
            'membershipLedger.membershipTransaction.farmer.barangay:id,name',
            'membershipLedger.membershipTransaction.farmer.memberType:id,code,name',
            'membershipLedger.membershipTransaction:id,farmer_id',
            'filedBy:id,name',
        ];
    }

    private function eligibleFarmerQueueQuery(array $filters = []): Builder
    {
        return Farmer::query()
            ->with([
                'profile:farmer_id,first_name,middle_name,last_name,suffix',
                'memberType:id,code,name',
                'barangay:id,name',
                'association:id,name',
                'membershipLedgers.membershipTransaction.paymentAssessments:id,membership_transaction_id,total_amount_due,mortuary_fee',
                'membershipLedgers.membershipTransaction:id,farmer_id',
                'membershipLedgers.mortuaryClaims',
            ])
            ->whereNull('inactive_at')
            ->whereHas('membershipLedgers', function (Builder $query): void {
                $query
                    ->where('mortuary_eligible', true)
                    ->whereIn('payment_status', [
                        $this->ledgerPaymentStatusValue(PaymentStatus::PAID),
                        $this->ledgerPaymentStatusValue(PaymentStatus::OVERPAID),
                        $this->ledgerPaymentStatusValue(PaymentStatus::WAIVED),
                    ]);
            })
            ->whereDoesntHave('membershipLedgers.mortuaryClaims', function (Builder $query): void {
                $query->where('status', '!=', $this->databaseStatusValue('rejected'));
            })
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $farmerQuery) use ($search): void {
                    $farmerQuery
                        ->where('farmer_code', 'like', "%{$search}%")
                        ->orWhereHas('profile', function (Builder $profileQuery) use ($search): void {
                            $profileQuery
                                ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('middle_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($filters['year'] ?? null, function (Builder $query, string $year): void {
                $query->whereHas('membershipLedgers', function (Builder $ledgerQuery) use ($year): void {
                    $ledgerQuery
                        ->where('year', (int) $year)
                        ->where('mortuary_eligible', true)
                        ->whereIn('payment_status', [
                            $this->ledgerPaymentStatusValue(PaymentStatus::PAID),
                            $this->ledgerPaymentStatusValue(PaymentStatus::OVERPAID),
                            $this->ledgerPaymentStatusValue(PaymentStatus::WAIVED),
                        ]);
                });
            })
            ->when($filters['barangay_id'] ?? null, fn (Builder $query, string $barangayId) => $query->where('barangay_id', $barangayId));
    }

    private function serializeClaimRow(MortuaryClaim $claim): array
    {
        $farmer = $claim->farmer;

        return [
            'id' => $claim->id,
            'claimReference' => $claim->claim_reference,
            'claimDate' => optional($claim->claim_date)->format('M d, Y'),
            'claimAmount' => (float) $claim->claim_amount,
            'status' => [
                'value' => $claim->status,
                'label' => match ($claim->status) {
                    'pending' => 'For Review',
                    'approved', 'released' => 'Completed',
                    default => str((string) $claim->status)->replace('_', ' ')->title()->toString(),
                },
            ],
            'filedBy' => $claim->filedBy?->name ?? 'System',
            'farmer' => [
                'fullName' => $farmer?->full_name ?? 'Unknown Farmer',
                'farmerCode' => $farmer?->farmer_code ?? 'No code',
                'barangay' => $farmer?->barangay?->name,
                'memberType' => $farmer?->memberType ? [
                    'code' => $farmer->memberType->code,
                    'name' => $farmer->memberType->name,
                ] : null,
            ],
            'ledger' => [
                'year' => $claim->membershipLedger?->year,
                'paymentStatus' => $claim->membershipLedger?->payment_status
                    ? str((string) $claim->membershipLedger->payment_status)->replace('_', ' ')->title()->toString()
                    : 'No payment status',
            ],
            'actions' => [
                'showUrl' => route('admin.mortuary-claims.show', $claim),
            ],
        ];
    }

    private function serializeEligibleFarmerRow(Farmer $farmer): array
    {
        $settledLedgers = $this->settledMortuaryLedgers($farmer);
        $representativeLedger = $this->representativeMortuaryLedger($farmer);
        $expectedClaim = round((float) $settledLedgers->sum(fn (MembershipLedger $item): float => (float) $item->mortuary_fee), 2);

        return [
            'id' => $farmer->id,
            'queueType' => 'eligible_farmer',
            'farmer' => [
                'fullName' => $farmer?->full_name ?? 'Unknown Farmer',
                'farmerCode' => $farmer?->farmer_code ?? 'No code',
                'barangay' => $farmer?->barangay?->name,
                'association' => $farmer?->association?->name,
                'memberType' => $farmer?->memberType ? [
                    'code' => $farmer->memberType->code,
                    'name' => $farmer->memberType->name,
                ] : null,
            ],
            'ledger' => [
                'year' => $representativeLedger?->year,
                'paymentStatus' => $representativeLedger?->payment_status
                    ? str((string) $representativeLedger->payment_status)->replace('_', ' ')->title()->toString()
                    : 'No payment status',
                'contributionYears' => $settledLedgers->count(),
            ],
            'claimAmount' => $expectedClaim,
            'status' => [
                'value' => 'eligible',
                'label' => 'Eligible',
            ],
            'actions' => [
                'createUrl' => $representativeLedger
                    ? route('admin.mortuary-claims.create', ['membership_ledger_id' => $representativeLedger->id])
                    : null,
            ],
        ];
    }

    private function serializeEligibleFarmerForCreate(Farmer $farmer): array
    {
        $settledLedgers = $this->settledMortuaryLedgers($farmer);
        $representativeLedger = $this->representativeMortuaryLedger($farmer);
        $expectedClaim = round((float) $settledLedgers->sum(fn (MembershipLedger $item): float => (float) $item->mortuary_fee), 2);

        return [
            'id' => $representativeLedger?->id,
            'label' => trim(($farmer?->full_name ?? 'Unknown Farmer') . ' - ' . ($farmer?->farmer_code ?? 'No code')),
            'year' => $representativeLedger?->year,
            'expectedClaimAmount' => $expectedClaim,
            'contributionYears' => $settledLedgers->count(),
            'paymentStatusLabel' => $representativeLedger?->payment_status
                ? 'Payment ' . str((string) $representativeLedger->payment_status)->replace('_', ' ')->title()->toString()
                : 'No payment status',
            'farmer' => [
                'id' => $farmer?->id,
                'fullName' => $farmer?->full_name ?? 'Unknown Farmer',
                'farmerCode' => $farmer?->farmer_code ?? 'No code',
                'barangay' => $farmer?->barangay?->name,
                'association' => $farmer?->association?->name,
                'memberType' => $farmer?->memberType ? [
                    'code' => $farmer->memberType->code,
                    'name' => $farmer->memberType->name,
                ] : null,
            ],
        ];
    }

    private function settledMortuaryLedgers(Farmer $farmer)
    {
        return $farmer->membershipLedgers
            ->filter(function (MembershipLedger $ledger): bool {
                return (bool) $ledger->mortuary_eligible
                    && in_array($ledger->payment_status, ['paid', 'overpaid', 'waived'], true);
            })
            ->sortBy([
                ['year', 'desc'],
                ['id', 'desc'],
            ])
            ->values();
    }

    private function representativeMortuaryLedger(?Farmer $farmer): ?MembershipLedger
    {
        if (! $farmer) {
            return null;
        }

        return $this->settledMortuaryLedgers($farmer)->first();
    }

    private function serializeClaimDetail(MortuaryClaim $claim, bool $approvalRequirementsComplete): array
    {
        $farmer = $claim->farmer;
        $lastUpdatedBy = $claim->releasedByUser?->name
            ?? $claim->approvedByUser?->name
            ?? $claim->filedBy?->name;
        $lastUpdatedAt = $claim->updated_at?->format('M d, Y h:i A');

        return [
            'id' => $claim->id,
            'claimReference' => $claim->claim_reference,
            'claimDate' => optional($claim->claim_date)->format('M d, Y'),
            'claimAmount' => (float) $claim->claim_amount,
            'status' => [
                'value' => $claim->status,
                'label' => match ($claim->status) {
                    'pending' => 'For Review',
                    default => str((string) $claim->status)->replace('_', ' ')->title()->toString(),
                },
            ],
            'remarks' => $claim->remarks,
            'filedBy' => $claim->filedBy?->name ?? 'System',
            'approvedBy' => $claim->approvedByUser?->name,
            'releasedBy' => $claim->releasedByUser?->name,
            'accountability' => [
                'lastUpdatedBy' => $lastUpdatedBy,
                'lastUpdatedAt' => $lastUpdatedAt,
            ],
            'approvalRequirementsComplete' => $approvalRequirementsComplete,
            'farmer' => [
                'id' => $farmer?->id,
                'fullName' => $farmer?->full_name ?? 'Unknown Farmer',
                'farmerCode' => $farmer?->farmer_code ?? 'No code',
                'memberType' => $farmer?->memberType ? [
                    'code' => $farmer->memberType->code,
                    'name' => $farmer->memberType->name,
                ] : null,
                'statusLabel' => $farmer?->status?->label() ?? null,
                'barangay' => $farmer?->barangay?->name,
                'association' => $farmer?->association?->name,
            ],
            'ledger' => [
                'year' => $claim->membershipLedger?->year,
                'paymentStatusLabel' => $claim->membershipLedger?->payment_status
                    ? str((string) $claim->membershipLedger->payment_status)->replace('_', ' ')->title()->toString()
                    : 'Not recorded',
                'mortuaryEligible' => (bool) ($claim->membershipLedger?->mortuary_eligible ?? false),
                'amountPaid' => (float) ($claim->membershipLedger?->amount_paid ?? 0),
                'memberTypeSnapshot' => $claim->membershipLedger?->member_type_snapshot,
            ],
            'checklist' => $this->serializeClaimChecklist($claim),
            'claimer' => [
                'name' => $claim->claimer_name,
                'relationship' => $claim->claimer_relationship,
                'contactNumber' => $claim->claimer_contact_number,
                'address' => $claim->claimer_address,
            ],
            'canReview' => $claim->status === 'pending',
        ];
    }

    private function databaseStatusValue(string $status): string
    {
        return match (strtolower($status)) {
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'released' => 'Released',
            default => 'Pending',
        };
    }

    private function splitClaimerName(string $fullName): array
    {
        $parts = collect(preg_split('/\s+/', trim($fullName)) ?: [])->filter()->values();

        if ($parts->isEmpty()) {
            return ['', null, ''];
        }

        if ($parts->count() === 1) {
            return [$parts[0], null, $parts[0]];
        }

        if ($parts->count() === 2) {
            return [$parts[0], null, $parts[1]];
        }

        return [
            (string) $parts->shift(),
            $parts->slice(0, -1)->implode(' ') ?: null,
            (string) $parts->last(),
        ];
    }

    private function syncClaimRequirements(MortuaryClaim $claim, array $validated, $requirements): void
    {
        foreach ($requirements as $requirement) {
            $code = strtolower((string) $requirement->documentType?->code);

            if ($code === '') {
                continue;
            }

            $type = ClaimRequirementType::query()->firstOrCreate(
                ['code' => $code],
                [
                    'name' => $requirement->documentType?->name ?: $requirement->documentType?->code ?: 'Requirement',
                    'description' => $requirement->documentType?->name ?: $requirement->documentType?->code ?: 'Requirement',
                    'status' => 'Active',
                ],
            );

            $claim->requirements()->updateOrCreate(
                ['requirement_type_id' => $type->id],
                [
                    'is_received' => (bool) data_get($validated, "requirements.$code.is_received", false),
                    'verified_by' => Auth::id(),
                    'verified_at' => now(),
                    'remarks' => null,
                ],
            );
        }
    }

    private function activeMortuaryRequirements()
    {
        return DocumentRequirement::query()
            ->with('documentType:id,code,name')
            ->where('transaction_type', 'Mortuary')
            ->where('is_active', true)
            ->where('is_required', true)
            ->orderBy('id')
            ->get();
    }

    private function claimRequirementsComplete(MortuaryClaim $claim): bool
    {
        $requiredCodes = $this->activeMortuaryRequirements()
            ->map(fn (DocumentRequirement $requirement): string => strtolower((string) $requirement->documentType?->code))
            ->filter()
            ->values();

        if ($requiredCodes->isEmpty()) {
            return true;
        }

        $claim->loadMissing('requirements.requirementType');
        $receivedCodes = $claim->requirements
            ->filter(fn ($requirement): bool => (bool) $requirement->is_received)
            ->map(fn ($requirement): string => strtolower((string) $requirement->requirementType?->code))
            ->filter()
            ->values();

        return $requiredCodes->every(fn (string $code): bool => $receivedCodes->contains($code));
    }

    private function serializeClaimChecklist(MortuaryClaim $claim): array
    {
        $claim->loadMissing('requirements.requirementType');

        return [
            'items' => $this->activeMortuaryRequirements()
                ->map(function (DocumentRequirement $requirement) use ($claim): array {
                    $code = strtolower((string) $requirement->documentType?->code);
                    $received = $claim->requirements->contains(function ($item) use ($code): bool {
                        return strtolower((string) $item->requirementType?->code) === $code
                            && (bool) $item->is_received;
                    });

                    return [
                        'code' => $code,
                        'label' => $requirement->documentType?->name ?: $requirement->documentType?->code ?: 'Requirement',
                        'received' => $received,
                    ];
                })
                ->values()
                ->all(),
        ];
    }

    private function ledgerPaymentStatusValue(PaymentStatus $status): string
    {
        return match ($status) {
            PaymentStatus::PAID => 'Paid',
            PaymentStatus::OVERPAID => 'Overpaid',
            PaymentStatus::WAIVED => 'Waived',
            PaymentStatus::PARTIALLY_PAID => 'Partial',
            default => 'Unpaid',
        };
    }

    private function filters(Request $request): array
    {
        return [
            'search' => $request->filled('search') ? (string) $request->input('search') : '',
            'status' => $request->filled('status') ? (string) $request->input('status') : '',
            'year' => $request->filled('year') ? (string) $request->input('year') : '',
            'barangay_id' => $request->filled('barangay_id') ? (string) $request->input('barangay_id') : '',
        ];
    }

    private function selectedQueueColumns(Request $request): array
    {
        $available = array_keys(MortuaryQueueExport::availableColumns());
        $requested = collect($request->input('columns', []))
            ->map(fn ($value): string => (string) $value)
            ->filter(fn (string $value): bool => in_array($value, $available, true))
            ->values()
            ->all();

        return $requested !== [] ? $requested : array_keys(MortuaryQueueExport::availableColumns());
    }

    private function selectedRecordColumns(Request $request): array
    {
        $available = array_keys(MortuaryRecordsExport::availableColumns());
        $requested = collect($request->input('columns', []))
            ->map(fn ($value): string => (string) $value)
            ->filter(fn (string $value): bool => in_array($value, $available, true))
            ->values()
            ->all();

        return $requested !== [] ? $requested : array_keys(MortuaryRecordsExport::availableColumns());
    }

    private function buildQueueExportRows(iterable $farmers): \Illuminate\Support\Collection
    {
        return collect($farmers)->map(function (Farmer $farmer): array {
            $row = $this->serializeEligibleFarmerRow($farmer);

            return [
                'farmer_code' => $row['farmer']['farmerCode'] ?? '',
                'full_name' => $row['farmer']['fullName'] ?? '',
                'barangay' => $row['farmer']['barangay'] ?? '',
                'association' => $row['farmer']['association'] ?? '',
                'member_type' => $row['farmer']['memberType']['name'] ?? $row['farmer']['memberType']['code'] ?? '',
                'ledger_year' => $row['ledger']['year'] ?? '',
                'contribution_years' => $row['ledger']['contributionYears'] ?? 0,
                'claim_amount' => number_format((float) ($row['claimAmount'] ?? 0), 2, '.', ''),
                'status' => $row['status']['label'] ?? '',
            ];
        })->values();
    }

    private function buildRecordExportRows(iterable $claims): \Illuminate\Support\Collection
    {
        return collect($claims)->map(function (MortuaryClaim $claim): array {
            $row = $this->serializeClaimRow($claim);

            return [
                'claim_reference' => $row['claimReference'] ?? '',
                'claim_date' => $row['claimDate'] ?? '',
                'full_name' => $row['farmer']['fullName'] ?? '',
                'farmer_code' => $row['farmer']['farmerCode'] ?? '',
                'barangay' => $row['farmer']['barangay'] ?? '',
                'member_type' => $row['farmer']['memberType']['name'] ?? $row['farmer']['memberType']['code'] ?? '',
                'ledger_year' => $row['ledger']['year'] ?? '',
                'payment_status' => $row['ledger']['paymentStatus'] ?? '',
                'claim_amount' => number_format((float) ($row['claimAmount'] ?? 0), 2, '.', ''),
                'status' => $row['status']['label'] ?? '',
                'filed_by' => $row['filedBy'] ?? '',
            ];
        })->values();
    }
}
