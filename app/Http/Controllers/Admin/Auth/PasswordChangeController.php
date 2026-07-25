<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Audit\AuditTrailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PasswordChangeController extends Controller
{
    public function __construct(
        private readonly AuditTrailService $auditTrailService,
    ) {
    }

    public function edit(Request $request): View
    {
        return view('admin.auth.password-change', [
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => [
                'required',
                'confirmed',
                Password::min(8),
                function (string $attribute, mixed $value, \Closure $fail) use ($request): void {
                    $user = $request->user();

                    if ($user !== null && Hash::check((string) $value, (string) $user->password)) {
                        $fail('The new password must be different from the current password.');
                    }
                },
            ],
        ]);

        /** @var User $user */
        $user = $request->user();
        $user->forceFill([
            'password' => $validated['password'],
        ])->save();

        $this->auditTrailService->record(
            'auth',
            'password_changed',
            'Updated the office account password.',
            $user,
            $user
        );

        return redirect()->route($this->redirectRouteFor($user))
            ->with('success', 'Password updated successfully.');
    }

    private function redirectRouteFor(User $user): string
    {
        if (in_array($user->role, [User::ROLE_ADMIN, User::ROLE_STAFF], true) && Route::has('admin.reports.index')) {
            return 'admin.reports.index';
        }

        return 'admin.farmers.index';
    }
}
