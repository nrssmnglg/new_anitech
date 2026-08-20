<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Farmer;
use App\Models\OfficeProfile;
use App\Models\User;
use App\Services\Audit\AuditTrailService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('role.in:' . User::ROLE_ADMIN);
    }

    public function index(Request $request): InertiaResponse
    {
        $filters = [
            'status' => $request->query('status'),
            'account_type' => $request->query('account_type'),
        ];

        $users = User::query()
            ->select(['id', 'name', 'email', 'role', 'status', 'created_at'])
            ->with([
                'officeProfile:id,user_id,employee_id,job_title,contact_number',
                'farmer:id,farmer_code',
            ])
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['account_type'] ?? null, function (Builder $query, string $accountType): void {
                match ($accountType) {
                    'farmer' => $query->where('role', User::ROLE_FARMER),
                    'staff' => $query->whereIn('role', [User::ROLE_ADMIN, User::ROLE_STAFF]),
                    default => null,
                };
            })
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        $adminCount = User::query()
            ->where('role', User::ROLE_ADMIN)
            ->where('status', User::STATUS_ACTIVE)
            ->count();
        $currentUserId = Auth::id();
        $summary = User::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active")
            ->selectRaw("SUM(CASE WHEN role = 'Farmer' THEN 1 ELSE 0 END) as farmers")
            ->selectRaw("SUM(CASE WHEN role IN ('Admin', 'Staff') THEN 1 ELSE 0 END) as staff")
            ->first();

        return Inertia::render('Admin/Users/Index', [
            'filters' => $filters,
            'summary' => [
                'total' => (int) ($summary?->total ?? 0),
                'active' => (int) ($summary?->active ?? 0),
                'farmers' => (int) ($summary?->farmers ?? 0),
                'staff' => (int) ($summary?->staff ?? 0),
            ],
            'filterOptions' => [
                'statuses' => [
                    ['value' => '', 'label' => 'All statuses'],
                    ['value' => User::STATUS_ACTIVE, 'label' => 'Active'],
                    ['value' => User::STATUS_INACTIVE, 'label' => 'Inactive'],
                ],
                'accountTypes' => [
                    ['value' => '', 'label' => 'All account types'],
                    ['value' => 'farmer', 'label' => 'Farmer Accounts'],
                    ['value' => 'staff', 'label' => 'Office Accounts'],
                ],
            ],
            'users' => $users->through(fn (User $user): array => $this->serializeIndexUser($user, $adminCount, $currentUserId)),
            'urls' => [
                'index' => route('admin.users.index'),
                'create' => route('admin.users.create'),
            ],
        ]);
    }

    public function create(): InertiaResponse
    {
        return Inertia::render('Admin/Users/Create', [
            'user' => $this->serializeFormUser(new User(), new OfficeProfile()),
            'roleOptions' => $this->selectOptions($this->roleOptions()),
            'statusOptions' => $this->selectOptions($this->statusOptions()),
            'farmerOptions' => $this->farmerOptions(),
            'employeeIdPreview' => $this->previewEmployeeId(),
            'urls' => [
                'index' => route('admin.users.index'),
                'store' => route('admin.users.store'),
            ],
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $role = $validated['role'];
        $payload = $this->userPayload($validated);
        $generatedPassword = $this->generateDefaultPassword();
        $payload['password'] = $generatedPassword;
        $payload['created_by'] = Auth::id();
        $payload['must_change_password'] = $role !== User::ROLE_FARMER;
        $payload['farmer_id'] = $role === User::ROLE_FARMER ? ($validated['farmer_id'] ?? null) : null;

        $user = User::query()->create($payload);
        $this->syncOfficeProfile($user, $validated);

        app(AuditTrailService::class)->record(
            'users',
            'user_created',
            'Created a user account.',
            Auth::user(),
            $user,
            [
                'email' => $user->email,
                'role' => $role,
                'status' => $user->status,
            ]
        );

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', 'User account created.')
            ->with('generatedCredentials', [
                'employee_id' => $user->officeProfile?->employee_id,
                'password' => $generatedPassword,
            ]);
    }

    public function show(User $user): InertiaResponse
    {
        $user->load([
            'officeProfile:id,user_id,employee_id,job_title,contact_number',
            'farmer:id,farmer_code',
        ]);

        return Inertia::render('Admin/Users/Show', [
            'user' => $this->serializeShowUser($user),
            'generatedCredentials' => session('generatedCredentials'),
            'urls' => [
                'index' => route('admin.users.index'),
                'edit' => route('admin.users.edit', $user),
                'archive' => $this->canArchive($user) ? route('admin.users.archive', $user) : null,
            ],
        ]);
    }

    public function edit(User $user): InertiaResponse
    {
        $user->load([
            'officeProfile:id,user_id,employee_id,job_title,contact_number',
            'farmer:id,farmer_code',
        ]);

        return Inertia::render('Admin/Users/Edit', [
            'user' => $this->serializeFormUser($user, $user->officeProfile ?? new OfficeProfile()),
            'roleOptions' => $this->selectOptions($this->roleOptions()),
            'statusOptions' => $this->selectOptions($this->statusOptions()),
            'farmerOptions' => $this->farmerOptions($user->farmer_id),
            'employeeIdPreview' => $user->officeProfile?->employee_id ?: $this->previewEmployeeId($user->role),
            'canArchive' => $this->canArchive($user),
            'urls' => [
                'index' => route('admin.users.index'),
                'show' => route('admin.users.show', $user),
                'update' => route('admin.users.update', $user),
                'archive' => $this->canArchive($user) ? route('admin.users.archive', $user) : null,
            ],
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();
        $role = $validated['role'];
        $before = [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'status' => $user->status,
            'job_title' => $user->officeProfile?->job_title,
            'contact_number' => $user->officeProfile?->contact_number,
        ];

        if (
            $user->hasRole(User::ROLE_ADMIN)
            && $role !== User::ROLE_ADMIN
            && ! User::query()->where('role', User::ROLE_ADMIN)->whereKeyNot($user->id)->exists()
        ) {
            return back()
                ->withInput()
                ->with('error', 'At least one administrator account must remain assigned the administrator role.');
        }

        $payload = $this->userPayload($validated);
        $payload['farmer_id'] = $role === User::ROLE_FARMER ? ($validated['farmer_id'] ?? null) : null;

        if (filled($validated['password'] ?? null) && $role !== User::ROLE_FARMER) {
            $payload['must_change_password'] = true;
        }

        $user->update($payload);
        $this->syncOfficeProfile($user, $validated);
        $user->load('officeProfile');

        app(AuditTrailService::class)->recordChange(
            'users',
            'user_updated',
            'Updated a user account.',
            Auth::user(),
            $user,
            $before,
            [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
                'job_title' => $user->officeProfile?->job_title,
                'contact_number' => $user->officeProfile?->contact_number,
            ]
        );

        if (($before['role'] ?? null) !== $user->role) {
            app(AuditTrailService::class)->record(
                'users',
                'user_role_changed',
                'Changed a user account role.',
                Auth::user(),
                $user,
                [
                    'previous_role' => $before['role'],
                    'new_role' => $user->role,
                ]
            );
        }

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', 'User account updated.');
    }

    public function archive(User $user): RedirectResponse
    {
        if (! $this->canArchive($user)) {
            return redirect()
                ->route('admin.users.index')
                ->with('error', Auth::id() === $user->id
                    ? 'You cannot archive the account you are currently using.'
                    : 'At least one active administrator account must remain in the system.');
        }

        $user->forceFill([
            'status' => User::STATUS_INACTIVE,
        ])->save();

        app(AuditTrailService::class)->record(
            'users',
            'user_archived',
            'Archived a user account.',
            Auth::user(),
            $user,
            [
                'email' => $user->email,
                'name' => $user->name,
                'role' => $user->role,
                'status' => $user->status,
            ]
        );

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User account archived.');
    }

    private function serializeIndexUser(User $user, int $adminCount, ?int $currentUserId): array
    {
        $canArchive = ! ($currentUserId === $user->id || ($user->role === User::ROLE_ADMIN && $adminCount <= 1))
            && $user->status === User::STATUS_ACTIVE;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role ?: 'Unassigned',
            'status' => $user->status,
            'createdAt' => optional($user->created_at)->format('M d, Y'),
            'accountType' => $user->role === User::ROLE_FARMER ? 'Farmer Account' : 'Office Account',
            'farmer' => $user->farmer ? [
                'code' => $user->farmer->farmer_code,
            ] : null,
            'officeProfile' => $user->officeProfile ? [
                'employeeId' => $user->officeProfile->employee_id,
                'jobTitle' => $user->officeProfile->job_title,
            ] : null,
            'actions' => [
                'show' => route('admin.users.show', $user),
                'edit' => route('admin.users.edit', $user),
                'archive' => $canArchive ? route('admin.users.archive', $user) : null,
            ],
        ];
    }

    private function serializeShowUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role ?: 'Unassigned',
            'status' => $user->status,
            'createdAt' => optional($user->created_at)->format('M d, Y h:i A') ?? 'Unknown',
            'updatedAt' => optional($user->updated_at)->format('M d, Y h:i A') ?? 'Unknown',
            'canArchive' => $this->canArchive($user),
            'farmer' => $user->farmer ? [
                'code' => $user->farmer->farmer_code,
            ] : null,
            'officeProfile' => $user->officeProfile ? [
                'employeeId' => $user->officeProfile->employee_id,
                'jobTitle' => $user->officeProfile->job_title,
                'contactNumber' => $user->officeProfile->contact_number,
            ] : null,
        ];
    }

    private function serializeFormUser(User $user, OfficeProfile $officeProfile): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role ?: '',
            'status' => $user->status ?: User::STATUS_ACTIVE,
            'farmer_id' => $user->farmer_id,
            'job_title' => $officeProfile->job_title,
            'contact_number' => $officeProfile->contact_number,
            'employee_id' => $officeProfile->employee_id,
        ];
    }

    private function farmerOptions(?int $selectedFarmerId = null): array
    {
        return Farmer::query()
            ->with(['profile:id,farmer_id,first_name,middle_name,last_name,suffix'])
            ->orderByDesc('id')
            ->limit(500)
            ->get(['id', 'farmer_code', 'membership_status', 'inactive_at', 'inactive_reason'])
            ->map(function (Farmer $farmer) use ($selectedFarmerId): array {
                $profile = $farmer->profile;
                $name = $profile ? trim(collect([
                    $profile->first_name,
                    $profile->middle_name,
                    $profile->last_name,
                    $profile->suffix,
                ])->filter()->implode(' ')) : $farmer->farmer_code;

                return [
                    'value' => $farmer->id,
                    'label' => $name . ' (' . $farmer->farmer_code . ')',
                    'disabled' => $selectedFarmerId !== null && (int) $selectedFarmerId !== (int) $farmer->id && $farmer->users()->exists(),
                ];
            })
            ->values()
            ->all();
    }

    private function selectOptions(array $map): array
    {
        return collect($map)
            ->map(fn (string $label, string $value): array => ['value' => $value, 'label' => $label])
            ->values()
            ->all();
    }

    private function roleOptions(): array
    {
        return [
            User::ROLE_ADMIN => 'Administrator',
            User::ROLE_STAFF => 'Staff',
            User::ROLE_FARMER => 'Farmer',
        ];
    }

    private function statusOptions(): array
    {
        return [
            User::STATUS_ACTIVE => 'Active',
            User::STATUS_INACTIVE => 'Inactive',
        ];
    }

    private function userPayload(array $validated): array
    {
        if (blank($validated['password'] ?? null)) {
            unset($validated['password']);
        }

        return $validated;
    }

    private function canArchive(User $user): bool
    {
        if (Auth::id() === $user->id) {
            return false;
        }

        if ($user->role === User::ROLE_ADMIN && $user->status === User::STATUS_ACTIVE) {
            return User::query()
                ->where('role', User::ROLE_ADMIN)
                ->where('status', User::STATUS_ACTIVE)
                ->whereKeyNot($user->id)
                ->exists();
        }

        return true;
    }

    private function syncOfficeProfile(User $user, array $validated): void
    {
        if (! in_array($validated['role'] ?? null, [User::ROLE_ADMIN, User::ROLE_STAFF], true)) {
            $user->officeProfile()?->delete();

            return;
        }

        [$firstName, $middleName, $lastName] = $this->splitOfficeName((string) $user->name);

        $user->officeProfile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'first_name' => $firstName,
                'middle_name' => $middleName,
                'last_name' => $lastName,
                'suffix' => null,
                'employee_id' => $user->officeProfile?->employee_id ?: $this->generateEmployeeId($user, (string) $validated['role']),
                'job_title' => $validated['job_title'] ?? null,
                'contact_number' => $validated['contact_number'] ?? null,
            ]
        );
    }

    private function generateEmployeeId(User $user, string $role): string
    {
        $prefix = match ($role) {
            User::ROLE_ADMIN => 'ADM',
            User::ROLE_STAFF => 'STF',
            default => 'USR',
        };

        return sprintf('%s-%s-%04d', $prefix, now()->format('Y'), $user->id);
    }

    private function previewEmployeeId(?string $role = null): string
    {
        $role ??= User::ROLE_STAFF;
        $prefix = match ($role) {
            User::ROLE_ADMIN => 'ADM',
            User::ROLE_STAFF => 'STF',
            default => 'USR',
        };

        $nextUserId = (int) User::query()->max('id') + 1;

        return sprintf('%s-%s-%04d', $prefix, now()->format('Y'), $nextUserId);
    }

    private function generateDefaultPassword(): string
    {
        return 'AniTech@' . Str::upper(Str::random(8));
    }

    private function splitOfficeName(string $fullName): array
    {
        $parts = collect(preg_split('/\s+/', trim($fullName)) ?: [])
            ->filter()
            ->values();

        if ($parts->isEmpty()) {
            return ['Office', null, 'User'];
        }

        if ($parts->count() === 1) {
            return [(string) $parts[0], null, (string) $parts[0]];
        }

        if ($parts->count() === 2) {
            return [(string) $parts[0], null, (string) $parts[1]];
        }

        return [
            (string) $parts->shift(),
            $parts->slice(0, -1)->implode(' ') ?: null,
            (string) $parts->last(),
        ];
    }
}
