<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FarmerPasswordResetController;
use App\Http\Controllers\Api\MembershipApplicationController;
use App\Models\Association;
use App\Models\Barangay;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::prefix('farmer')->name('farmer.pwa.')->group(function (): void {
    Route::get('/', fn () => redirect()->route('farmer.pwa.app'));

    Route::post('/application', [MembershipApplicationController::class, 'store'])
        ->withoutMiddleware([VerifyCsrfToken::class])
        ->name('application.store');

    Route::get('/application/track', [MembershipApplicationController::class, 'track'])
        ->name('application.track');

    Route::post('/application/{applicationNo}/documents', [MembershipApplicationController::class, 'uploadDocument'])
        ->withoutMiddleware([VerifyCsrfToken::class])
        ->name('application.documents.store');

    Route::post('/application/{applicationNo}/payment', [MembershipApplicationController::class, 'pay'])
        ->withoutMiddleware([VerifyCsrfToken::class])
        ->name('application.payment.store');

    Route::get('/application/{applicationNo}/payment/qr', [MembershipApplicationController::class, 'qrPage'])
        ->name('application.payment.qr');

    Route::post('/account-setup', [AuthController::class, 'setup'])
        ->withoutMiddleware([VerifyCsrfToken::class])
        ->name('account.setup.store');

    Route::get('/login', fn () => redirect(url('/farmer/app/login')))->name('login');
    Route::get('/home', fn () => redirect(url('/farmer/app/dashboard')))->name('home');
    Route::get('/payments', fn () => redirect(url('/farmer/app/payments')))->name('payments');
    Route::get('/alerts', fn () => redirect(url('/farmer/app/advisories')))->name('alerts');
    Route::get('/renewal', fn () => redirect(url('/farmer/app/renewals')))->name('renewal');
    Route::get('/queries', fn () => redirect(url('/farmer/app/inquiries')))->name('queries.index');

    Route::get('/advisories/{advisory}', fn (string $advisory) => redirect(url('/farmer/app/advisories/' . $advisory)))
        ->name('advisories.show');

    Route::get('/advisories/{advisory}/attachments/{attachment}', fn (string $advisory) => redirect(url('/farmer/app/advisories/' . $advisory)))
        ->name('advisories.attachments.show');

    Route::get('/queries/{query}', fn () => redirect(url('/farmer/app/inquiries')))
        ->name('queries.show');

    Route::get('/queries/{query}/images/{image}', fn () => redirect(url('/farmer/app/inquiries')))
        ->name('queries.images.show');

    Route::get('/queries/{query}/responses/{response}/attachments/{attachment}', fn () => redirect(url('/farmer/app/inquiries')))
        ->name('queries.responses.attachments.show');

    Route::get('/renewal/{renewal}/payment/qr', fn () => redirect(url('/farmer/app/renewals')))
        ->name('renewal.payment.qr');

    Route::middleware('guest:farmer_pwa')->group(function (): void {
        Route::get('/forgot-password', fn () => redirect(url('/farmer/app/forgot-password')))->name('password.request');
        Route::post('/forgot-password', [FarmerPasswordResetController::class, 'storeForgot'])
            ->withoutMiddleware([VerifyCsrfToken::class])
            ->name('password.email');
        Route::get('/reset-password', fn (\Illuminate\Http\Request $request) => redirect(url('/farmer/app/reset-password/verify?email=' . urlencode((string) $request->string('email')))))->name('password.reset');
        Route::post('/reset-password/verify', [FarmerPasswordResetController::class, 'verifyOtp'])
            ->withoutMiddleware([VerifyCsrfToken::class])
            ->name('password.verify');
        Route::get('/reset-password/new', [FarmerPasswordResetController::class, 'createNewPassword'])
            ->name('password.create');
        Route::post('/reset-password', [FarmerPasswordResetController::class, 'storeReset'])
            ->withoutMiddleware([VerifyCsrfToken::class])
            ->name('password.store');
    });

    Route::get('/apply', fn () => redirect(url('/farmer/app/apply')))->name('apply');

    Route::get('/upload', fn () => redirect(url('/farmer/app/upload?' . http_build_query(request()->only(['application_no', 'birth_date'])))))->name('upload');
    Route::get('/upload-complete', fn () => redirect(url('/farmer/app/upload-complete?' . http_build_query(request()->only(['application_no', 'birth_date'])))))->name('upload.complete');
    Route::get('/track', fn () => redirect(url('/farmer/app/track?' . http_build_query(request()->only(['application_no', 'birth_date'])))))->name('track');
    Route::get('/track-status', fn () => redirect(url('/farmer/app/track-status?' . http_build_query(request()->only(['application_no', 'birth_date'])))))->name('track.status');
    Route::get('/account-setup', fn () => redirect(url('/farmer/app/setup-account?' . http_build_query(request()->only(['application_no', 'birth_date'])))))->name('account.setup');

    Route::get('/app/{any?}', function () {
        return view('farmer.pwa-vue', [
            'shell' => [
                'authenticated' => Auth::guard('farmer_pwa')->check(),
                'user' => Auth::guard('farmer_pwa')->user()?->only(['id', 'name', 'email']),
                'apiBase' => url('/api/farmer'),
                'appBase' => url('/farmer/app'),
                'logoUrl' => asset('figures/anitech-mark-official.svg'),
                'publicData' => [
                    'barangays' => Barangay::query()->orderBy('name')->get(['id', 'name']),
                    'associations' => Association::query()->orderBy('name')->get(['id', 'barangay_id', 'name']),
                ],
            ],
        ]);
    })->where('any', '.*')->name('app');
});
