<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Audit\AuditTrailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    private const MAX_LOGIN_ATTEMPTS = 3;
    private const LOGIN_LOCKOUT_SECONDS = 900;

    public function __construct(
        private readonly AuditTrailService $auditTrailService,
    ) {
    }

    public function create(): Response|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route($this->redirectRouteFor(Auth::user()));
        }

        return Inertia::render('Admin/Auth/Login', [
            'forgotPasswordUrl' => route('password.request'),
            'loginUrl' => route('login.attempt'),
            'logoUrl' => asset('figures/anitech-logo-official.svg'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_LOGIN_ATTEMPTS)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'password' => $this->lockoutMessage($throttleKey),
                ]);
        }

        /** @var User|null $user */
        $user = User::query()
            ->where('email', $credentials['email'])
            ->first();

        if (! $user || ! in_array($user->role, [User::ROLE_ADMIN, User::ROLE_STAFF], true)) {
            RateLimiter::hit($throttleKey, self::LOGIN_LOCKOUT_SECONDS);
            $this->auditTrailService->record(
                'auth',
                'login_rejected',
                'Portal login rejected because no matching portal account was found.',
                null,
                null,
                ['email' => $credentials['email']]
            );

            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'No portal account was found for this email address.',
                ]);
        }

        if (! Hash::check($credentials['password'], $user->getAuthPassword())) {
            $attempts = RateLimiter::hit($throttleKey, self::LOGIN_LOCKOUT_SECONDS);
            $remainingAttempts = max(self::MAX_LOGIN_ATTEMPTS - $attempts, 0);
            $this->auditTrailService->record(
                'auth',
                'login_failed',
                'Portal login failed because of an invalid password.',
                $user,
                null,
                [
                    'email' => $credentials['email'],
                    'remaining_attempts' => $remainingAttempts,
                ]
            );

            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'password' => $remainingAttempts > 0
                        ? "Incorrect password. You have {$remainingAttempts} attempt".($remainingAttempts === 1 ? '' : 's')." remaining before a 15-minute lockout."
                        : $this->lockoutMessage($throttleKey),
                ]);
        }

        RateLimiter::clear($throttleKey);

        if ($user->status !== User::STATUS_ACTIVE) {
            $this->auditTrailService->record(
                'auth',
                'login_blocked_inactive',
                'Portal login was blocked because the account is inactive.',
                $user
            );

            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'This account is inactive.',
                ]);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        $this->auditTrailService->record(
            'auth',
            'login_succeeded',
            'Portal login succeeded.',
            $user
        );

        return redirect()->intended(route($this->redirectRouteFor($user)));
    }

    private function redirectRouteFor(?User $user): string
    {
        if ($user && in_array($user->role, [User::ROLE_ADMIN, User::ROLE_STAFF], true) && Route::has('admin.reports.index')) {
            return 'admin.reports.index';
        }

        return 'admin.farmers.index';
    }

    private function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower((string) $request->input('email')).'|'.$request->ip());
    }

    private function lockoutMessage(string $throttleKey): string
    {
        $seconds = RateLimiter::availableIn($throttleKey);
        $minutes = max((int) ceil($seconds / 60), 1);

        return "Too many failed login attempts. Please try again in {$minutes} minute".($minutes === 1 ? '' : 's')." or use Forgot password.";
    }
}
