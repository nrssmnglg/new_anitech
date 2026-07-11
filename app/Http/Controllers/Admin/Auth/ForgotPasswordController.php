<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Models\OfficePasswordResetOtp;
use App\Models\User;
use App\Notifications\OfficePasswordResetOtpNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class ForgotPasswordController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Admin/Auth/ForgotPassword', [
            'loginUrl' => route('login'),
            'submitUrl' => route('password.email'),
            'logoUrl' => asset('figures/anitech-logo-official.svg'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $officeUser = User::query()
            ->where('email', $validated['email'])
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_STAFF])
            ->first();

        if (! $officeUser) {
            return back()->with('status', 'If the email is registered to an office account, a one-time password has been sent.');
        }

        OfficePasswordResetOtp::query()->where('user_id', $officeUser->id)->delete();

        $code = (string) random_int(100000, 999999);

        OfficePasswordResetOtp::query()->create([
            'user_id' => $officeUser->id,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(10),
        ]);

        $officeUser->notify(new OfficePasswordResetOtpNotification($code));

        return redirect()
            ->route('password.reset', ['email' => $officeUser->email])
            ->with('status', 'We emailed a 6-digit OTP for your office account password reset.');
    }
}
