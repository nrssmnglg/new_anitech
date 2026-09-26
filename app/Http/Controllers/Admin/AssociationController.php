<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAssociationRequest;
use App\Http\Requests\Admin\UpdateAssociationRequest;
use App\Models\Association;
use App\Models\Barangay;
use App\Models\Farmer;
use App\Models\User;
use App\Services\Routing\PublicRouteKeyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class AssociationController extends Controller
{
    public function __construct()
    {
        $this->middleware('role.in:' . User::ROLE_ADMIN);
    }

    public function index(Request $request): InertiaResponse
    {
        $filters = $request->only(['search', 'status', 'barangay_id']);
        $queryFilters = [
            ...$filters,
            'barangay_id' => $this->decodeQueryRouteKey((string) ($filters['barangay_id'] ?? '')),
        ];
        $canonicalAssociationIds = Association::query()
            ->selectRaw('MIN(id)')
            ->groupBy('barangay_id');

        $associationsQuery = Association::query()
            ->whereIn('id', $canonicalAssociationIds)
            ->with('barangay:id,name,code')
            ->withCount('farmers')
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($nested) use ($search): void {
                    $nested
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhereHas('barangay', function ($barangayQuery) use ($search): void {
                            $barangayQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('code', 'like', "%{$search}%");
                        });
                });
            })
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($queryFilters['barangay_id'] ?? null, fn ($query, int $barangayId) => $query->where('barangay_id', $barangayId));

        $associations = (clone $associationsQuery)
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        $associations->through(fn (Association $association): array => $this->serializeAssociationRow($association));

        return Inertia::render('Admin/Associations/Index', [
            'associations' => $associations,
            'filters' => $filters,
            'summary' => [
                'total' => (clone $associationsQuery)->count(),
                'active' => (clone $associationsQuery)->whereRaw('LOWER(status) = ?', ['active'])->count(),
                'inactive' => (clone $associationsQuery)->whereRaw('LOWER(status) = ?', ['inactive'])->count(),
            ],
            'urls' => [
                'index' => route('admin.associations.index'),
                'create' => route('admin.associations.create'),
                'barangays' => route('admin.barangays.index'),
            ],
            'filterOptions' => [
                'statuses' => collect($this->statusOptions())
                    ->map(fn (string $label, string $value): array => ['value' => $value, 'label' => $label])
                    ->values()
                    ->all(),
                'barangays' => Barangay::query()
                    ->orderBy('name')
                    ->get(['id', 'name', 'code'])
                    ->map(fn (Barangay $barangay): array => [
                        'value' => $this->queryRouteKey($barangay->id),
                        'label' => trim($barangay->name . ($barangay->code ? " ({$barangay->code})" : '')),
                    ])
                    ->values()
                    ->all(),
            ],
        ]);
    }

    public function create(): InertiaResponse
    {
        return Inertia::render('Admin/Associations/Create', [
            'association' => [
                'barangay_id' => '',
                'name' => '',
                'code' => '',
                'president_name' => '',
                'status' => 'active',
            ],
            'barangays' => $this->availableBarangays()
                ->map(fn (Barangay $barangay): array => $this->serializeAvailableBarangay($barangay))
                ->values()
                ->all(),
            'dependencyWarnings' => [],
            'statusOptions' => $this->statusOptions(),
            'urls' => [
                'index' => route('admin.associations.index'),
                'store' => route('admin.associations.store'),
            ],
        ]);
    }

    public function store(StoreAssociationRequest $request): RedirectResponse
    {
        $association = DB::transaction(function () use ($request): Association {
            $codes = Association::query()
                ->whereRaw("code REGEXP '^ASSOC[0-9]+$'")
                ->lockForUpdate()
                ->pluck('code');
            $nextNumber = $codes
                ->map(fn (?string $code): int => preg_match('/^ASSOC(\d+)$/', (string) $code, $matches) ? (int) $matches[1] : 0)
                ->max() + 1;
            $code = 'ASSOC' . str_pad((string) $nextNumber, 2, '0', STR_PAD_LEFT);

            return Association::query()->create([
                ...$request->validated(),
                'code' => $code,
            ]);
        }, 3);

        return redirect()
            ->route('admin.associations.show', $association)
            ->with('success', 'Association record created.');
    }

    public function show(Association $association): InertiaResponse
    {
        $association->load([
            'barangay:id,name,code,status',
            'president.profile:id,farmer_id,first_name,middle_name,last_name,suffix,mobile_number',
            'farmers.profile:id,farmer_id,first_name,middle_name,last_name,suffix,mobile_number',
            'farmers.memberType:id,code,name',
        ])
            ->loadCount('farmers');

        return Inertia::render('Admin/Associations/Show', [
            'association' => $this->serializeAssociationDetail($association),
            'stats' => [
                'farmers_count' => (int) ($association->farmers_count ?? 0),
            ],
            'farmers' => $association->farmers
                ->sortBy(fn ($farmer): string => strtolower($farmer->full_name))
                ->values()
                ->map(fn ($farmer): array => [
                    'id' => $farmer->id,
                    'farmerCode' => $farmer->farmer_code,
                    'fullName' => $farmer->full_name,
                    'memberType' => $farmer->memberType?->name ?? 'Not set',
                    'mobileNumber' => $farmer->profile?->mobile_number ?? 'Not recorded',
                    'status' => $farmer->status->label(),
                    'statusValue' => $farmer->status->value,
                    'showUrl' => route('admin.farmers.show', $farmer),
                ])
                ->all(),
            'urls' => [
                'index' => route('admin.associations.index'),
                'edit' => route('admin.associations.edit', $association),
                'farmers' => route('admin.farmers.index', ['association_id' => $this->queryRouteKey($association->id)]),
                'barangay' => $association->barangay
                    ? route('admin.barangays.show', $association->barangay)
                    : null,
            ],
        ]);
    }

    public function edit(Association $association): InertiaResponse
    {
        return Inertia::render('Admin/Associations/Edit', [
            'association' => [
                'id' => $association->id,
                'barangay_id' => $this->queryRouteKey($association->barangay_id),
                'name' => $association->name,
                'code' => $association->code,
                'president_name' => $association->president_name,
                'president_farmer_id' => $this->queryRouteKey($association->president_farmer_id),
                'status' => $association->status ?: 'active',
            ],
            'presidentCandidates' => $association->farmers()
                ->with('profile:id,farmer_id,first_name,middle_name,last_name,suffix,mobile_number')
                ->get()
                ->sortBy(fn (Farmer $farmer): string => strtolower($farmer->full_name))
                ->values()
                ->map(fn (Farmer $farmer): array => [
                    'id' => $this->queryRouteKey($farmer->id),
                    'name' => $farmer->full_name,
                    'farmerCode' => $farmer->farmer_code,
                    'mobileNumber' => $farmer->profile?->mobile_number ?? 'No contact number',
                ])
                ->all(),
            'barangays' => $this->availableBarangays($association)
                ->map(fn (Barangay $barangay): array => $this->serializeAvailableBarangay($barangay))
                ->values()
                ->all(),
            'dependencyWarnings' => $this->dependencyWarnings($association),
            'statusOptions' => $this->statusOptions(),
            'urls' => [
                'index' => route('admin.associations.index'),
                'show' => route('admin.associations.show', $association),
                'update' => route('admin.associations.update', $association),
            ],
        ]);
    }

    public function update(UpdateAssociationRequest $request, Association $association): RedirectResponse
    {
        $validated = $request->validated();
        $president = filled($validated['president_farmer_id'] ?? null)
            ? Farmer::query()->find($validated['president_farmer_id'])
            : null;

        $association->update([
            ...$validated,
            'president_name' => $president?->full_name,
        ]);

        return redirect()
            ->route('admin.associations.show', $association)
            ->with('success', 'Association record updated.');
    }

    public function toggleStatus(Request $request, Association $association): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys($this->statusOptions()))],
        ]);

        $association->update([
            'status' => $validated['status'],
        ]);

        $message = 'Association marked as ' . $validated['status'] . '.';
        $warnings = $validated['status'] === 'inactive'
            ? $this->dependencyWarnings($association->fresh())
            : [];

        if ($warnings !== []) {
            $message .= ' Warning: ' . implode(' ', $warnings);
        }

        return back()->with('success', $message);
    }

    private function availableBarangays(?Association $association = null)
    {
        return Barangay::query()
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'status']);
    }

    private function statusOptions(): array
    {
        return [
            'active' => 'Active',
            'inactive' => 'Inactive',
        ];
    }

    private function dependencyWarnings(Association $association): array
    {
        $association->loadCount('farmers')->loadMissing('barangay:id,name,status');
        $warnings = [];

        if ($association->farmers_count > 0) {
            $warnings[] = "This association is linked to {$association->farmers_count} farmer record(s).";
        }

        if ($association->barangay?->status === 'inactive') {
            $warnings[] = 'Its barangay is currently inactive.';
        }

        return $warnings;
    }

    private function serializeAssociationRow(Association $association): array
    {
        $status = strtolower((string) $association->status);

        return [
            'id' => $association->id,
            'name' => $association->name,
            'code' => $association->code,
            'president_name' => $association->president_name,
            'status' => [
                'value' => $status,
                'label' => ucfirst($status),
            ],
            'farmersCount' => (int) $association->farmers_count,
            'barangay' => $association->barangay ? [
                'name' => $association->barangay->name,
                'code' => $association->barangay->code,
            ] : null,
            'actions' => [
                'showUrl' => route('admin.associations.show', $association),
                'editUrl' => route('admin.associations.edit', $association),
                'toggleStatusUrl' => route('admin.associations.status', $association),
            ],
        ];
    }

    private function serializeAssociationDetail(Association $association): array
    {
        $status = strtolower((string) $association->status);

        return [
            'id' => $association->id,
            'name' => $association->name,
            'code' => $association->code,
            'president_name' => $association->president_name,
            'president' => $association->president ? [
                'name' => $association->president->full_name,
                'farmerCode' => $association->president->farmer_code,
                'contactNumber' => $association->president->profile?->mobile_number,
                'showUrl' => route('admin.farmers.show', $association->president),
            ] : null,
            'is_active' => $status === 'active',
            'contact_number' => $association->contact_number,
            'address' => $association->address,
            'notes' => $association->notes,
            'status' => [
                'value' => $status,
                'label' => ucfirst($status),
            ],
            'farmersCount' => (int) $association->farmers_count,
            'barangay' => $association->barangay ? [
                'name' => $association->barangay->name,
                'code' => $association->barangay->code,
                'status' => ucfirst(strtolower((string) $association->barangay->status)),
            ] : null,
        ];
    }

    private function serializeAvailableBarangay(Barangay $barangay): array
    {
        return [
            'id' => $this->queryRouteKey($barangay->id),
            'name' => $barangay->name,
            'code' => $barangay->code,
            'status' => ucfirst((string) $barangay->status),
        ];
    }

    private function queryRouteKey(int|string|null $id): string
    {
        return $id === null || $id === '' ? '' : app(PublicRouteKeyService::class)->encode($id);
    }

    private function decodeQueryRouteKey(string $key): ?int
    {
        if ($key === '') {
            return null;
        }

        if (is_numeric($key)) {
            return (int) $key;
        }

        return app(PublicRouteKeyService::class)->decode($key);
    }
}
