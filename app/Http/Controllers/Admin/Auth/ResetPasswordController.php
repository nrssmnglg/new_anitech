<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Models\OfficePasswordResetOtp;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Inertia\Inertia;
use Inertia\Response;

class ResetPasswordController extends Controller
{
    private const VERIFIED_EMAIL_SESSION_KEY = 'office_password_reset.verified_email';

    public function create(Request $request): Response
    {
        return Inertia::render('Admin/Auth/VerifyOtp', [
            'email' => (string) $request->string('email'),
            'submitUrl' => route('password.verify'),
            'logoUrl' => asset('figures/anitech-logo-official.svg'),
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'digits:6'],
        ]);

        $officeUser = $this->officeUserByEmail($validated['email']);

        if (! $officeUser) {
            return back()
                ->withInput()
                ->withErrors(['email' => 'This email address is not eligible for office portal password reset.']);
        }

        $otp = OfficePasswordResetOtp::query()
            ->where('user_id', $officeUser->id)
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (! $otp || ! Hash::check($validated['otp'], $otp->code_hash)) {
            return back()
                ->withInput()
                ->withErrors(['otp' => 'The OTP is invalid or has expired.']);
        }

        $request->session()->put(self::VERIFIED_EMAIL_SESSION_KEY, $officeUser->email);

        return redirect()
            ->route('password.create', ['email' => $officeUser->email])
            ->with('status', 'OTP verified. You can now choose a new office account password.');
    }

    public function createNewPassword(Request $request): RedirectResponse|Response
    {
        $email = (string) $request->string('email');

        if (! $this->hasVerifiedEmail($request, $email)) {
            return redirect()
                ->route('password.reset', ['email' => $email])
                ->withErrors(['otp' => 'Verify your OTP first before setting a new password.']);
        }

        return Inertia::render('Admin/Auth/NewPassword', [
            'email' => $email,
            'submitUrl' => route('password.store'),
            'logoUrl' => asset('figures/anitech-logo-official.svg'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $officeUser = $this->officeUserByEmail((string) $request->string('email'));

        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => [
                'required',
                'confirmed',
                PasswordRule::min(8),
                function (string $attribute, mixed $value, \Closure $fail) use ($officeUser): void {
                    if ($officeUser !== null && Hash::check((string) $value, (string) $officeUser->password)) {
                        $fail('The new password must be different from the current password.');
                    }
                },
            ],
        ]);

        if (! $this->hasVerifiedEmail($request, $validated['email'])) {
            return redirect()
                ->route('password.reset', ['email' => $validated['email']])
                ->withErrors(['otp' => 'Verify your OTP first before setting a new password.']);
        }

        $officeUser = $this->officeUserByEmail($validated['email']);

        if (! $officeUser) {
            $request->session()->forget(self::VERIFIED_EMAIL_SESSION_KEY);

            return redirect()
                ->route('password.request')
                ->withErrors(['email' => 'This email address is not eligible for office portal password reset.']);
        }

        $officeUser->forceFill([
            'password' => $request->string('password')->toString(),
            'remember_token' => Str::random(60),
        ])->save();

        OfficePasswordResetOtp::query()->where('user_id', $officeUser->id)->delete();
        $request->session()->forget(self::VERIFIED_EMAIL_SESSION_KEY);

        return redirect()->route('login')->with('status', 'Your office password has been reset successfully.');
    }

    private function officeUserByEmail(string $email): ?User
    {
        return User::query()
            ->where('email', $email)
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_STAFF])
            ->first();
    }

    private function hasVerifiedEmail(Request $request, string $email): bool
    {
        return $email !== '' && $request->session()->get(self::VERIFIED_EMAIL_SESSION_KEY) === $email;
    }
}
