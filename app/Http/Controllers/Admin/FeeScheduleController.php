<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFeeScheduleRequest;
use App\Http\Requests\Admin\UpdateFeeScheduleRequest;
use App\Models\FeeSchedule;
use App\Models\MemberType;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class FeeScheduleController extends Controller
{
    public function __construct()
    {
        $this->middleware('role.in:' . User::ROLE_ADMIN);
    }

    public function index(): InertiaResponse
    {
        $this->normalizeActiveSchedules();

        $schedules = FeeSchedule::query()
            ->select([
                'id',
                'member_type_id',
                'year',
                'membership_fee',
                'annual_due',
                'mortuary_fee',
                'renewal_deadline',
                'effective_from',
                'effective_to',
                'is_active',
            ])
            ->with('memberType:id,code,name')
            ->withCount('paymentAssessments')
            ->orderByDesc('year')
            ->paginate(12);

        $schedules->through(fn (FeeSchedule $schedule): array => $this->serializeScheduleRow($schedule));

        $activeSchedules = FeeSchedule::query()
            ->where('is_active', true)
            ->with('memberType:id,code,name')
            ->orderBy('member_type_id')
            ->orderByDesc('year')
            ->get([
                'id',
                'member_type_id',
                'year',
                'membership_fee',
                'annual_due',
                'mortuary_fee',
                'renewal_deadline',
                'effective_from',
                'effective_to',
                'is_active',
            ]);

        return Inertia::render('Admin/FeeSchedules/Index', [
            'schedules' => $schedules,
            'activeSchedules' => $activeSchedules
                ->map(fn (FeeSchedule $schedule): array => $this->serializeActiveSchedule($schedule))
                ->values()
                ->all(),
            'summary' => [
                'total' => FeeSchedule::query()->count(),
                'active' => FeeSchedule::query()->where('is_active', true)->count(),
                'activeMemberTypes' => FeeSchedule::query()->where('is_active', true)->distinct('member_type_id')->count('member_type_id'),
                'current_year' => FeeSchedule::query()->where('year', now()->year)->count(),
                'latest_year' => FeeSchedule::query()->max('year') ?? now()->year,
            ],
            'urls' => [
                'index' => route('admin.fee-schedules.index'),
                'create' => route('admin.fee-schedules.create'),
            ],
        ]);
    }

    public function create(): InertiaResponse
    {
        $this->normalizeActiveSchedules();

        return Inertia::render('Admin/FeeSchedules/Create', [
            'feeSchedule' => [
                'member_type_id' => null,
                'year' => now()->year,
                'renewal_deadline' => now()->endOfYear()->toDateString(),
                'effective_from' => now()->startOfYear()->toDateString(),
                'effective_to' => null,
                'membership_fee' => 100,
                'annual_due' => 100,
                'mortuary_fee' => 150,
                'is_active' => false,
            ],
            'activateByDefault' => false,
            'feeRules' => $this->feeRules(),
            'memberTypeOptions' => $this->memberTypeOptions(),
            'urls' => [
                'index' => route('admin.fee-schedules.index'),
                'store' => route('admin.fee-schedules.store'),
            ],
        ]);
    }

    public function store(StoreFeeScheduleRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $shouldActivate = ($validated['is_active'] ?? false)
            || ! FeeSchedule::query()->where('member_type_id', $validated['member_type_id'])->where('is_active', true)->exists();

        $feeSchedule = DB::transaction(fn (): FeeSchedule => $this->persistSchedule(null, $validated, $shouldActivate));

        return redirect()
            ->route('admin.fee-schedules.index')
            ->with('success', "Fee schedule for {$feeSchedule->year} created successfully.");
    }

    public function edit(FeeSchedule $feeSchedule): InertiaResponse
    {
        $this->normalizeActiveSchedules();

        $feeSchedule->refresh();

        return Inertia::render('Admin/FeeSchedules/Edit', [
            'feeSchedule' => $this->serializeFormSchedule($feeSchedule),
            'activateByDefault' => $feeSchedule->is_active,
            'feeRules' => $this->feeRules(),
            'memberTypeOptions' => $this->memberTypeOptions(),
            'urls' => [
                'index' => route('admin.fee-schedules.index'),
                'update' => route('admin.fee-schedules.update', $feeSchedule),
            ],
        ]);
    }

    public function update(UpdateFeeScheduleRequest $request, FeeSchedule $feeSchedule): RedirectResponse
    {
        $validated = $request->validated();
        $mustRemainActive = $feeSchedule->is_active
            && ! FeeSchedule::query()
                ->where('member_type_id', $feeSchedule->member_type_id)
                ->where('is_active', true)
                ->whereKeyNot($feeSchedule->id)
                ->exists();
        $shouldActivate = ($validated['is_active'] ?? false) || $mustRemainActive;

        DB::transaction(fn (): FeeSchedule => $this->persistSchedule($feeSchedule, $validated, $shouldActivate));

        $message = "Fee schedule for {$feeSchedule->year} updated successfully.";

        if ($mustRemainActive && ! ($validated['is_active'] ?? false)) {
            $message .= ' The schedule stayed active because the system must always keep one active fee configuration for this member type.';
        }

        return redirect()
            ->route('admin.fee-schedules.index')
            ->with('success', $message);
    }

    public function activate(FeeSchedule $feeSchedule): RedirectResponse
    {
        if ($feeSchedule->is_active) {
            return back()->with('success', "Fee schedule for {$feeSchedule->year} is already active.");
        }

        DB::transaction(function () use ($feeSchedule): void {
            FeeSchedule::query()
                ->where('member_type_id', $feeSchedule->member_type_id)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            $feeSchedule->forceFill([
                'is_active' => true,
            ])->save();
        });

        return back()->with('success', "Fee schedule for {$feeSchedule->year} is now active.");
    }

    public function destroy(FeeSchedule $feeSchedule): RedirectResponse
    {
        if ($feeSchedule->paymentAssessments()->exists()) {
            return back()->withErrors([
                'fee_schedule' => "Fee schedule for {$feeSchedule->year} cannot be deleted because it already has linked assessments.",
            ]);
        }

        if ($feeSchedule->is_active) {
            return back()->withErrors([
                'fee_schedule' => "Active fee schedule for {$feeSchedule->year} cannot be deleted. Activate another schedule for this member type first.",
            ]);
        }

        $feeSchedule->delete();

        return back()->with('success', "Fee schedule for {$feeSchedule->year} deleted.");
    }

    private function persistSchedule(?FeeSchedule $feeSchedule, array $validated, bool $shouldActivate): FeeSchedule
    {
        if ($shouldActivate) {
            FeeSchedule::query()
                ->when($feeSchedule, fn ($query) => $query->whereKeyNot($feeSchedule->id))
                ->where('member_type_id', $validated['member_type_id'])
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        $payload = [
            'year' => $validated['year'],
            'member_type_id' => $validated['member_type_id'],
            'membership_fee' => $validated['membership_fee'],
            'annual_due' => $validated['annual_due'],
            'mortuary_fee' => $validated['mortuary_fee'],
            'renewal_deadline' => $validated['renewal_deadline'],
            'effective_from' => $validated['effective_from'],
            'effective_to' => $validated['effective_to'] ?? null,
            'is_active' => $shouldActivate,
        ];

        if ($feeSchedule) {
            $feeSchedule->update($payload);

            return $feeSchedule->fresh();
        }

        return FeeSchedule::query()->create($payload);
    }

    private function serializeScheduleRow(FeeSchedule $schedule): array
    {
        $deleteDisabledReason = null;

        if ($schedule->is_active) {
            $deleteDisabledReason = "This fee schedule is active. Activate another schedule for this member type first.";
        } elseif ((int) $schedule->payment_assessments_count > 0) {
            $deleteDisabledReason = "This fee schedule already has linked assessments and cannot be deleted.";
        }

        return [
            'id' => $schedule->id,
            'year' => $schedule->year,
            'memberType' => $schedule->memberType ? [
                'code' => $schedule->memberType->code,
                'name' => $schedule->memberType->name,
            ] : null,
            'fees' => [
                'membership' => (float) $schedule->membership_fee,
                'annual' => (float) $schedule->annual_due,
                'mortuary' => (float) $schedule->mortuary_fee,
            ],
            'renewalDeadline' => optional($schedule->renewal_deadline)->format('M d, Y'),
            'effectiveRange' => [
                'from' => optional($schedule->effective_from)->format('M d, Y'),
                'to' => optional($schedule->effective_to)->format('M d, Y') ?? 'Open-ended',
            ],
            'assessmentsCount' => (int) $schedule->payment_assessments_count,
            'status' => [
                'value' => $schedule->is_active ? 'active' : 'inactive',
                'label' => $schedule->is_active ? 'Active' : 'Inactive',
            ],
            'actions' => [
                'editUrl' => route('admin.fee-schedules.edit', $schedule),
                'activateUrl' => ! $schedule->is_active ? route('admin.fee-schedules.activate', $schedule) : null,
                'deleteUrl' => route('admin.fee-schedules.destroy', $schedule),
                'deleteDisabledReason' => $deleteDisabledReason,
            ],
        ];
    }

    private function serializeActiveSchedule(FeeSchedule $schedule): array
    {
        return [
            'id' => $schedule->id,
            'year' => $schedule->year,
            'memberType' => $schedule->memberType ? [
                'code' => $schedule->memberType->code,
                'name' => $schedule->memberType->name,
            ] : null,
            'renewalDeadline' => optional($schedule->renewal_deadline)->format('F d, Y') ?? 'Not set',
            'membershipFee' => (float) $schedule->membership_fee,
            'annualDue' => (float) $schedule->annual_due,
            'mortuaryFee' => (float) $schedule->mortuary_fee,
            'effectiveFrom' => optional($schedule->effective_from)->format('M d, Y'),
            'effectiveTo' => optional($schedule->effective_to)->format('M d, Y') ?? 'Open-ended',
        ];
    }

    private function serializeFormSchedule(FeeSchedule $schedule): array
    {
        return [
            'id' => $schedule->id,
            'member_type_id' => $schedule->member_type_id,
            'year' => $schedule->year,
            'membership_fee' => (float) $schedule->membership_fee,
            'annual_due' => (float) $schedule->annual_due,
            'mortuary_fee' => (float) $schedule->mortuary_fee,
            'renewal_deadline' => optional($schedule->renewal_deadline)->format('Y-m-d'),
            'effective_from' => optional($schedule->effective_from)->format('Y-m-d'),
            'effective_to' => optional($schedule->effective_to)->format('Y-m-d'),
            'is_active' => (bool) $schedule->is_active,
        ];
    }

    private function feeRules(): array
    {
        return [
            ['label' => 'New Member', 'description' => 'Membership fee + annual due + mortuary fee'],
            ['label' => 'Old Member', 'description' => 'Annual due + mortuary fee'],
            ['label' => 'New Senior Citizen', 'description' => 'Membership fee + annual due'],
            ['label' => 'Old Senior Citizen', 'description' => 'Annual due only'],
        ];
    }

    private function memberTypeOptions(): array
    {
        return MemberType::query()
            ->orderBy('code')
            ->get(['id', 'code', 'name'])
            ->map(fn (MemberType $memberType): array => [
                'id' => $memberType->id,
                'label' => $memberType->code . ' - ' . $memberType->name,
            ])
            ->values()
            ->all();
    }

    private function normalizeActiveSchedules(): void
    {
        $activeIds = FeeSchedule::query()
            ->where('is_active', true)
            ->orderBy('member_type_id')
            ->orderByDesc('year')
            ->orderByDesc('id')
            ->get(['id', 'member_type_id'])
            ->groupBy('member_type_id');

        foreach ($activeIds as $memberTypeSchedules) {
            if ($memberTypeSchedules->count() <= 1) {
                continue;
            }

            FeeSchedule::query()
                ->where('member_type_id', $memberTypeSchedules->first()->member_type_id)
                ->where('is_active', true)
                ->whereKeyNot($memberTypeSchedules->first()->id)
                ->update(['is_active' => false]);
        }
    }
}
