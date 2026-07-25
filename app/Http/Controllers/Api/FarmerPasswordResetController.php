<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FarmerPasswordResetOtp;
use App\Models\User;
use App\Notifications\FarmerResetPasswordNotification;
use App\Services\Analytics\AnalyticsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class FarmerPasswordResetController extends Controller
{
    private const VERIFIED_EMAIL_SESSION_KEY = 'farmer_password_reset.verified_email';
    private const OTP_TTL_MINUTES = 10;
    private const RESEND_COOLDOWN_SECONDS = 60;
    private const RESEND_WINDOW_MINUTES = 15;
    private const RESEND_MAX_ATTEMPTS = 3;
    private const VERIFY_WINDOW_MINUTES = 15;
    private const VERIFY_MAX_ATTEMPTS = 5;
    private const SUPPORT_MESSAGE = 'If OTP delivery keeps failing, contact AniTech support or visit the municipal agriculture office for account recovery assistance.';

    public function __construct(
        private readonly AnalyticsService $analyticsService,
    ) {
    }

    public function createForgot(): RedirectResponse
    {
        return redirect(url('/farmer/app/forgot-password'));
    }

    public function storeForgot(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = Str::lower($validated['email']);
        $farmerUser = $this->farmerUserByEmail($validated['email']);
        $message = 'If the email is registered to an active farmer account, a one-time password has been sent.';

        if ($farmerUser) {
            $resendState = $this->resendStateForUser($farmerUser->id);

            if ($resendState['blocked_until']?->isFuture()) {
                return $this->rateLimitedResponse(
                    $request,
                    'Too many OTP requests. Try again after the waiting period.',
                    'otp_resend_rate_limited',
                    'email',
                    $this->otpMeta($farmerUser->email, null, $resendState['blocked_until'], $resendState['attempts'], self::RESEND_MAX_ATTEMPTS, true),
                );
            }

            if ($resendState['cooldown_until']?->isFuture()) {
                return $this->rateLimitedResponse(
                    $request,
                    'Please wait before requesting another OTP.',
                    'otp_resend_cooldown',
                    'email',
                    $this->otpMeta($farmerUser->email, null, $resendState['cooldown_until'], $resendState['attempts'], self::RESEND_MAX_ATTEMPTS, false),
                );
            }
        }

        if (! $farmerUser) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'data' => [
                        'email' => $validated['email'],
                    ],
                    'meta' => [
                        'reason' => 'account_not_found',
                        'support_message' => self::SUPPORT_MESSAGE,
                    ],
                ]);
            }

            return back()->with('success', $message);
        }

        FarmerPasswordResetOtp::query()->where('user_id', $farmerUser->id)->delete();

        $code = (string) random_int(100000, 999999);
        $expiresAt = now()->addMinutes(self::OTP_TTL_MINUTES);

        FarmerPasswordResetOtp::query()->create([
            'user_id' => $farmerUser->id,
            'code_hash' => Hash::make($code),
            'expires_at' => $expiresAt,
        ]);

        $resendState = $this->incrementResendState($farmerUser->id);
        $farmerUser->notify(new FarmerResetPasswordNotification($code));

        $message = 'We emailed a 6-digit OTP for your farmer account password reset.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'data' => [
                    'email' => $farmerUser->email,
                    'redirect_url' => url('/farmer/app/reset-password/verify?email=' . urlencode($farmerUser->email)),
                ],
                'meta' => $this->otpMeta(
                    $farmerUser->email,
                    $expiresAt,
                    $resendState['cooldown_until'],
                    $resendState['attempts'],
                    self::RESEND_MAX_ATTEMPTS,
                    false,
                ),
            ]);
        }

        return redirect()
            ->route('farmer.pwa.password.reset', ['email' => $farmerUser->email])
            ->with('success', $message);
    }

    public function createReset(Request $request): RedirectResponse
    {
        return redirect(url('/farmer/app/reset-password/verify?email=' . urlencode((string) $request->string('email'))));
    }

    public function verifyOtp(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'digits:6'],
        ]);

        $farmerUser = $this->farmerUserByEmail($validated['email']);

        if (! $farmerUser) {
            return $this->validationFailure(
                $request,
                'This email address is not eligible for farmer account password reset.',
                'email',
                'account_not_found',
                [
                    'support_message' => self::SUPPORT_MESSAGE,
                ],
            );
        }

        $verifyState = $this->verifyStateForUser($farmerUser->id);

        if ($verifyState['blocked_until']?->isFuture()) {
            return $this->rateLimitedResponse(
                $request,
                'Too many OTP verification attempts. Try again after the waiting period.',
                'otp_verify_rate_limited',
                'otp',
                $this->otpMeta(
                    $farmerUser->email,
                    null,
                    $verifyState['blocked_until'],
                    $verifyState['attempts'],
                    self::VERIFY_MAX_ATTEMPTS,
                    true,
                ),
            );
        }

        $otp = FarmerPasswordResetOtp::query()
            ->where('user_id', $farmerUser->id)
            ->latest('id')
            ->first();

        if (! $otp) {
            return $this->validationFailure(
                $request,
                'No active OTP was found. Request a new code to continue.',
                'otp',
                'otp_not_found',
                [
                    'support_message' => self::SUPPORT_MESSAGE,
                ],
            );
        }

        if ($otp->expires_at->isPast()) {
            $this->analyticsService->track('farmer_otp_failed', [
                'module' => 'mobile_auth',
                'user_id' => $farmerUser->id,
                'farmer_id' => $farmerUser->farmer_id,
                'properties' => [
                    'reason' => 'expired_otp',
                ],
            ]);

            return $this->validationFailure(
                $request,
                'This OTP has expired. Request a new code to continue.',
                'otp',
                'otp_expired',
                $this->otpMeta(
                    $farmerUser->email,
                    $otp->expires_at,
                    null,
                    $verifyState['attempts'],
                    self::VERIFY_MAX_ATTEMPTS,
                    false,
                ),
            );
        }

        if (! Hash::check($validated['otp'], $otp->code_hash)) {
            $verifyState = $this->incrementVerifyState($farmerUser->id);
            $remainingAttempts = max(0, self::VERIFY_MAX_ATTEMPTS - $verifyState['attempts']);

            $this->analyticsService->track('farmer_otp_failed', [
                'module' => 'mobile_auth',
                'user_id' => $farmerUser->id,
                'farmer_id' => $farmerUser->farmer_id,
                'properties' => [
                    'reason' => 'invalid_otp',
                    'attempts' => $verifyState['attempts'],
                ],
            ]);

            return $this->validationFailure(
                $request,
                $remainingAttempts > 0
                    ? 'The OTP you entered is invalid. Please check the code and try again.'
                    : 'Too many invalid OTP attempts. Please wait before trying again.',
                'otp',
                $remainingAttempts > 0 ? 'otp_invalid' : 'otp_verify_rate_limited',
                array_merge(
                    $this->otpMeta(
                        $farmerUser->email,
                        $otp->expires_at,
                        $verifyState['blocked_until'] ?? null,
                        $verifyState['attempts'],
                        self::VERIFY_MAX_ATTEMPTS,
                        $remainingAttempts <= 0,
                    ),
                    ['support_message' => self::SUPPORT_MESSAGE],
                ),
                $remainingAttempts <= 0 ? 429 : 422,
            );
        }

        $request->session()->put(self::VERIFIED_EMAIL_SESSION_KEY, $farmerUser->email);
        $this->clearVerifyState($farmerUser->id);

        $this->analyticsService->track('farmer_otp_verified', [
            'module' => 'mobile_auth',
            'user_id' => $farmerUser->id,
            'farmer_id' => $farmerUser->farmer_id,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'OTP verified. You can now choose a new farmer account password.',
                'data' => [
                    'email' => $farmerUser->email,
                    'redirect_url' => url('/farmer/app/reset-password/new?email=' . urlencode($farmerUser->email)),
                ],
                'meta' => [
                    'reason' => 'otp_verified',
                    'support_message' => self::SUPPORT_MESSAGE,
                ],
            ]);
        }

        return redirect()
            ->route('farmer.pwa.password.create', ['email' => $farmerUser->email])
            ->with('success', 'OTP verified. You can now choose a new farmer account password.');
    }

    public function createNewPassword(Request $request): RedirectResponse
    {
        $email = (string) $request->string('email');

        if (! $this->hasVerifiedEmail($request, $email)) {
            return redirect()
                ->route('farmer.pwa.password.reset', ['email' => $email])
                ->withErrors(['otp' => 'Verify your OTP first before setting a new password.']);
        }

        return redirect(url('/farmer/app/reset-password/new?email=' . urlencode($email)));
    }

    public function storeReset(Request $request): JsonResponse|RedirectResponse
    {
        $farmerUser = $this->farmerUserByEmail((string) $request->string('email'));

        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => [
                'required',
                'confirmed',
                PasswordRule::min(8),
                function (string $attribute, mixed $value, \Closure $fail) use ($farmerUser): void {
                    if ($farmerUser !== null && Hash::check((string) $value, (string) $farmerUser->password)) {
                        $fail('The new password must be different from the current password.');
                    }
                },
            ],
        ]);

        if (! $this->hasVerifiedEmail($request, $validated['email'])) {
            return $this->validationFailure(
                $request,
                'Verify your OTP first before setting a new password.',
                'otp',
                'otp_verification_required',
                [
                    'email' => $validated['email'],
                    'support_message' => self::SUPPORT_MESSAGE,
                ],
            );
        }

        $farmerUser = $this->farmerUserByEmail($validated['email']);

        if (! $farmerUser) {
            $request->session()->forget(self::VERIFIED_EMAIL_SESSION_KEY);

            return $this->validationFailure(
                $request,
                'This email address is not eligible for farmer account password reset.',
                'email',
                'account_not_found',
                [
                    'support_message' => self::SUPPORT_MESSAGE,
                ],
            );
        }

        $farmerUser->forceFill([
            'password' => $request->string('password')->toString(),
            'remember_token' => Str::random(60),
        ])->save();

        FarmerPasswordResetOtp::query()->where('user_id', $farmerUser->id)->delete();
        $request->session()->forget(self::VERIFIED_EMAIL_SESSION_KEY);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Your farmer password has been reset successfully.',
                'data' => [
                    'email' => $farmerUser->email,
                    'redirect_url' => url('/farmer/app/login'),
                ],
            ]);
        }

        return redirect()
            ->route('farmer.pwa.login')
            ->with('success', 'Your farmer password has been reset successfully.');
    }

    private function farmerUserByEmail(string $email): ?User
    {
        return User::query()
            ->whereRaw('LOWER(email) = ?', [Str::lower($email)])
            ->whereNotNull('farmer_id')
            ->first();
    }

    private function hasVerifiedEmail(Request $request, string $email): bool
    {
        return $email !== '' && $request->session()->get(self::VERIFIED_EMAIL_SESSION_KEY) === $email;
    }

    private function otpMeta(string $email, $expiresAt = null, $retryAt = null, int $attempts = 0, int $maxAttempts = 0, bool $isBlocked = false): array
    {
        $seconds = $retryAt ? max(0, now()->diffInSeconds($retryAt, false) * -1) : 0;

        return [
            'reason' => $isBlocked ? 'rate_limited' : 'otp_pending',
            'email' => $email,
            'expires_at' => optional($expiresAt)->toIso8601String(),
            'retry_available_at' => optional($retryAt)->toIso8601String(),
            'retry_seconds_remaining' => $seconds,
            'attempts_used' => $attempts,
            'attempts_remaining' => max(0, $maxAttempts - $attempts),
            'max_attempts' => $maxAttempts,
            'is_blocked' => $isBlocked,
            'support_message' => self::SUPPORT_MESSAGE,
        ];
    }

    private function resendStateForUser(int $userId): array
    {
        return [
            'attempts' => (int) Cache::get($this->resendAttemptsKey($userId), 0),
            'cooldown_until' => Cache::get($this->resendCooldownKey($userId)),
            'blocked_until' => Cache::get($this->resendBlockKey($userId)),
        ];
    }

    private function incrementResendState(int $userId): array
    {
        $attemptsKey = $this->resendAttemptsKey($userId);
        $attempts = (int) Cache::get($attemptsKey, 0) + 1;
        Cache::put($attemptsKey, $attempts, now()->addMinutes(self::RESEND_WINDOW_MINUTES));

        $cooldownUntil = now()->addSeconds(self::RESEND_COOLDOWN_SECONDS);
        Cache::put($this->resendCooldownKey($userId), $cooldownUntil, $cooldownUntil);

        $blockedUntil = null;

        if ($attempts >= self::RESEND_MAX_ATTEMPTS) {
            $blockedUntil = now()->addMinutes(self::RESEND_WINDOW_MINUTES);
            Cache::put($this->resendBlockKey($userId), $blockedUntil, $blockedUntil);
        }

        return [
            'attempts' => $attempts,
            'cooldown_until' => $cooldownUntil,
            'blocked_until' => $blockedUntil,
        ];
    }

    private function verifyStateForUser(int $userId): array
    {
        return [
            'attempts' => (int) Cache::get($this->verifyAttemptsKey($userId), 0),
            'blocked_until' => Cache::get($this->verifyBlockKey($userId)),
        ];
    }

    private function incrementVerifyState(int $userId): array
    {
        $attemptsKey = $this->verifyAttemptsKey($userId);
        $attempts = (int) Cache::get($attemptsKey, 0) + 1;
        Cache::put($attemptsKey, $attempts, now()->addMinutes(self::VERIFY_WINDOW_MINUTES));

        $blockedUntil = null;

        if ($attempts >= self::VERIFY_MAX_ATTEMPTS) {
            $blockedUntil = now()->addMinutes(self::VERIFY_WINDOW_MINUTES);
            Cache::put($this->verifyBlockKey($userId), $blockedUntil, $blockedUntil);
        }

        return [
            'attempts' => $attempts,
            'blocked_until' => $blockedUntil,
        ];
    }

    private function clearVerifyState(int $userId): void
    {
        Cache::forget($this->verifyAttemptsKey($userId));
        Cache::forget($this->verifyBlockKey($userId));
    }

    private function resendAttemptsKey(int $userId): string
    {
        return 'farmer_password_reset:resend_attempts:' . $userId;
    }

    private function resendCooldownKey(int $userId): string
    {
        return 'farmer_password_reset:resend_cooldown:' . $userId;
    }

    private function resendBlockKey(int $userId): string
    {
        return 'farmer_password_reset:resend_block:' . $userId;
    }

    private function verifyAttemptsKey(int $userId): string
    {
        return 'farmer_password_reset:verify_attempts:' . $userId;
    }

    private function verifyBlockKey(int $userId): string
    {
        return 'farmer_password_reset:verify_block:' . $userId;
    }

    private function validationFailure(
        Request $request,
        string $message,
        string $field,
        string $reason,
        array $meta = [],
        int $status = 422
    ): JsonResponse|RedirectResponse {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'errors' => [
                    $field => [$message],
                ],
                'meta' => array_merge(['reason' => $reason], $meta),
            ], $status);
        }

        return back()->withInput()->withErrors([$field => $message]);
    }

    private function rateLimitedResponse(
        Request $request,
        string $message,
        string $reason,
        string $field,
        array $meta
    ): JsonResponse|RedirectResponse {
        return $this->validationFailure($request, $message, $field, $reason, $meta, 429);
    }
}
