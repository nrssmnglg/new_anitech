<?php

namespace App\Http\Controllers\Api;

use App\Enums\ApplicationStatus;
use App\Enums\FarmerStatus;
use App\Enums\MembershipStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginFarmerRequest;
use App\Http\Requests\Api\SetupFarmerAccountRequest;
use App\Models\MembershipApplication;
use App\Models\User;
use App\Services\Analytics\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    public function __construct(
        private readonly AnalyticsService $analyticsService,
    ) {
    }

    public function login(LoginFarmerRequest $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();

        $user = User::query()
            ->with(['farmer.memberType'])
            ->where('email', $validated['email'])
            ->whereNotNull('farmer_id')
            ->first();

        if (! $user) {
            $this->analyticsService->track('farmer_login_failed', [
                'module' => 'mobile_auth',
                'properties' => [
                    'reason' => 'account_not_found',
                ],
            ]);

            throw ValidationException::withMessages([
                'email' => 'No farmer account was found for this email address.',
            ]);
        }

        if (! Hash::check($validated['password'], $user->getAuthPassword())) {
            $this->analyticsService->track('farmer_login_failed', [
                'module' => 'mobile_auth',
                'user_id' => $user->id,
                'farmer_id' => $user->farmer_id,
                'properties' => [
                    'reason' => 'invalid_password',
                ],
            ]);

            throw ValidationException::withMessages([
                'password' => 'Incorrect password.',
            ]);
        }

        if ($user->status !== \App\Models\User::STATUS_ACTIVE) {
            $this->analyticsService->track('farmer_login_failed', [
                'module' => 'mobile_auth',
                'user_id' => $user->id,
                'farmer_id' => $user->farmer_id,
                'properties' => [
                    'reason' => 'inactive_account',
                ],
            ]);

            throw ValidationException::withMessages([
                'email' => 'This farmer account is inactive. Set up your account again or contact the office for assistance.',
            ]);
        }

        if (! $user->farmer || $user->farmer->membership_status !== MembershipStatus::ACTIVE || $user->farmer->status !== FarmerStatus::ACTIVE) {
            $this->analyticsService->track('farmer_login_failed', [
                'module' => 'mobile_auth',
                'user_id' => $user->id,
                'farmer_id' => $user->farmer_id,
                'properties' => [
                    'reason' => 'membership_not_active',
                ],
            ]);

            throw ValidationException::withMessages([
                'email' => 'This farmer account is inactive. Set up your account again or contact the office for assistance.',
            ]);
        }

        Auth::guard('farmer_pwa')->login($user);
        $request->session()->regenerate();

        $this->analyticsService->track('farmer_login_succeeded', [
            'module' => 'mobile_auth',
            'user_id' => $user->id,
            'farmer_id' => $user->farmer_id,
        ]);

        $payload = [
            'message' => 'Welcome back to AniTech Farmer Mobile.',
            'data' => [
                'redirect_url' => route('farmer.pwa.home'),
                'account' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'member_type' => $user->farmer->memberType?->name,
                ],
            ],
        ];

        if ($request->expectsJson()) {
            return response()->json($payload);
        }

        return redirect()
            ->route('farmer.pwa.home')
            ->with('status', $payload['message']);
    }

    public function setup(SetupFarmerAccountRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $application = MembershipApplication::query()
            ->with(['farmer.profile', 'farmer.users'])
            ->where('application_no', $validated['application_no'])
            ->whereHas('farmer.profile', fn ($query) => $query->whereDate('birth_date', $validated['birth_date']))
            ->first();

        if (! $application) {
            throw ValidationException::withMessages([
                'application_no' => 'No membership application matched the application number and birth date you entered.',
            ]);
        }

        $farmer = $application->farmer;

        if (! $farmer) {
            abort(404);
        }

        if ($application->status !== ApplicationStatus::APPROVED) {
            throw ValidationException::withMessages([
                'account' => 'Account setup is available only after your membership application is approved.',
            ]);
        }

        $existingUser = $farmer->users()->orderByDesc('id')->first();

        validator($validated, [
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($existingUser?->id),
            ],
        ])->validate();

        $user = DB::transaction(function () use ($validated, $farmer, $existingUser): User {
            $farmer->forceFill([
                'membership_status' => MembershipStatus::ACTIVE->value,
                'activated_at' => $farmer->activated_at ?? now(),
                'inactive_at' => null,
                'inactive_reason' => null,
            ])->save();

            $user = $existingUser ?? new User();

            $user->forceFill([
                'name' => $farmer->full_name,
                'email' => $validated['email'],
                'password' => $validated['password'],
                'status' => User::STATUS_ACTIVE,
                'farmer_id' => $farmer->id,
                'role' => User::ROLE_FARMER,
            ])->save();

            Role::findOrCreate(User::ROLE_FARMER, 'web');
            $user->syncRoles([User::ROLE_FARMER]);

            return $user;
        });

        return response()->json([
            'message' => $existingUser
                ? 'Account setup completed successfully. Your farmer account is active again.'
                : 'Account setup completed successfully.',
            'data' => [
                'account' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'farmer_id' => $user->farmer_id,
                    'redirect_url' => route('farmer.pwa.login'),
                ],
            ],
        ], 201);
    }

    public function logout(Request $request): JsonResponse|RedirectResponse
    {
        Auth::guard('farmer_pwa')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $payload = [
            'message' => 'You have been logged out.',
            'data' => [
                'redirect_url' => route('farmer.pwa.login'),
            ],
        ];

        if ($request->expectsJson()) {
            return response()->json($payload);
        }

        return redirect()
            ->route('farmer.pwa.login')
            ->with('status', $payload['message']);
    }
}
