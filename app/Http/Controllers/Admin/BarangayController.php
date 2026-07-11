<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBarangayRequest;
use App\Http\Requests\Admin\UpdateBarangayRequest;
use App\Models\Barangay;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class BarangayController extends Controller
{
    public function __construct()
    {
        $this->middleware('role.in:' . User::ROLE_ADMIN);
    }

    public function index(Request $request): InertiaResponse
    {
        $filters = $request->only(['search', 'status', 'association']);
        $barangaysQuery = Barangay::query()
            ->with(['association:id,barangay_id,name,code'])
            ->withCount('farmers')
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($nested) use ($search): void {
                    $nested
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['association'] ?? null, function ($query, string $association): void {
                match ($association) {
                    'with' => $query->has('association'),
                    'without' => $query->doesntHave('association'),
                    default => null,
                };
            });

        $barangays = (clone $barangaysQuery)
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        $barangays->through(fn (Barangay $barangay): array => $this->serializeBarangayRow($barangay));

        return Inertia::render('Admin/Barangays/Index', [
            'barangays' => $barangays,
            'filters' => $filters,
            'summary' => [
                'total' => Barangay::query()->count(),
                'active' => Barangay::query()->where('status', 'active')->count(),
                'with_association' => Barangay::query()->has('association')->count(),
            ],
            'urls' => [
                'index' => route('admin.barangays.index'),
                'create' => route('admin.barangays.create'),
            ],
            'filterOptions' => [
                'statuses' => collect($this->statusOptions())
                    ->map(fn (string $label, string $value): array => ['value' => $value, 'label' => $label])
                    ->values()
                    ->all(),
                'associations' => [
                    ['value' => 'with', 'label' => 'With Association'],
                    ['value' => 'without', 'label' => 'Without Association'],
                ],
            ],
        ]);
    }

    public function create(): InertiaResponse
    {
        return Inertia::render('Admin/Barangays/Create', [
            'barangay' => [
                'name' => '',
                'code' => '',
                'status' => 'active',
            ],
            'dependencyWarnings' => [],
            'statusOptions' => $this->statusOptions(),
            'urls' => [
                'index' => route('admin.barangays.index'),
                'store' => route('admin.barangays.store'),
            ],
        ]);
    }

    public function store(StoreBarangayRequest $request): RedirectResponse
    {
        $barangay = Barangay::query()->create($request->validated());

        return redirect()
            ->route('admin.barangays.show', $barangay)
            ->with('success', 'Barangay record created.');
    }

    public function show(Barangay $barangay): InertiaResponse
    {
        $barangay->load(['association:id,barangay_id,name,code,status'])
            ->loadCount(['farmers', 'associations']);

        return Inertia::render('Admin/Barangays/Show', [
            'barangay' => $this->serializeBarangayDetail($barangay),
            'stats' => [
                'associations_count' => (int) ($barangay->associations_count ?? 0),
                'farmers_count' => (int) ($barangay->farmers_count ?? 0),
            ],
            'urls' => [
                'index' => route('admin.barangays.index'),
                'edit' => route('admin.barangays.edit', $barangay),
                'farmers' => route('admin.farmers.index', ['barangay_id' => $barangay->id]),
            ],
        ]);
    }

    public function edit(Barangay $barangay): InertiaResponse
    {
        return Inertia::render('Admin/Barangays/Edit', [
            'barangay' => [
                'id' => $barangay->id,
                'name' => $barangay->name,
                'code' => $barangay->code,
                'status' => $barangay->status ?: 'active',
            ],
            'dependencyWarnings' => $this->dependencyWarnings($barangay),
            'statusOptions' => $this->statusOptions(),
            'urls' => [
                'index' => route('admin.barangays.index'),
                'show' => route('admin.barangays.show', $barangay),
                'update' => route('admin.barangays.update', $barangay),
            ],
        ]);
    }

    public function update(UpdateBarangayRequest $request, Barangay $barangay): RedirectResponse
    {
        $barangay->update($request->validated());

        return redirect()
            ->route('admin.barangays.show', $barangay)
            ->with('success', 'Barangay record updated.');
    }

    public function toggleStatus(Request $request, Barangay $barangay): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys($this->statusOptions()))],
        ]);

        $barangay->update([
            'status' => $validated['status'],
        ]);

        $message = 'Barangay marked as ' . $validated['status'] . '.';
        $warnings = $validated['status'] === 'inactive'
            ? $this->dependencyWarnings($barangay->fresh())
            : [];

        if ($warnings !== []) {
            $message .= ' Warning: ' . implode(' ', $warnings);
        }

        return back()->with('success', $message);
    }

    private function statusOptions(): array
    {
        return [
            'active' => 'Active',
            'inactive' => 'Inactive',
        ];
    }

    private function dependencyWarnings(Barangay $barangay): array
    {
        $barangay->loadCount('farmers')->loadMissing('association:id,barangay_id,name,status');
        $warnings = [];

        if ($barangay->farmers_count > 0) {
            $warnings[] = "This barangay is linked to {$barangay->farmers_count} farmer record(s).";
        }

        if ($barangay->association !== null) {
            $warnings[] = 'This barangay already has an associated association record.';
        }

        return $warnings;
    }

    private function serializeBarangayRow(Barangay $barangay): array
    {
        $status = strtolower((string) $barangay->status);

        return [
            'id' => $barangay->id,
            'name' => $barangay->name,
            'code' => $barangay->code,
            'status' => [
                'value' => $status,
                'label' => ucfirst($status),
            ],
            'farmersCount' => (int) $barangay->farmers_count,
            'association' => $barangay->association ? [
                'name' => $barangay->association->name,
                'code' => $barangay->association->code,
            ] : null,
            'actions' => [
                'showUrl' => route('admin.barangays.show', $barangay),
                'editUrl' => route('admin.barangays.edit', $barangay),
                'toggleStatusUrl' => route('admin.barangays.status', $barangay),
            ],
        ];
    }

    private function serializeBarangayDetail(Barangay $barangay): array
    {
        $status = strtolower((string) $barangay->status);

        return [
            'id' => $barangay->id,
            'name' => $barangay->name,
            'code' => $barangay->code,
            'municipality' => 'SAN CARLOS CITY',
            'province' => 'PANGASINAN',
            'is_active' => $status === 'active',
            'status' => [
                'value' => $status,
                'label' => ucfirst($status),
            ],
            'farmersCount' => (int) $barangay->farmers_count,
            'association' => $barangay->association ? [
                'name' => $barangay->association->name,
                'code' => $barangay->association->code,
                'status' => ucfirst(strtolower((string) $barangay->association->status)),
            ] : null,
        ];
    }
}
