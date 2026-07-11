<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Services\Audit\AuditTrailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutController extends Controller
{
    public function __construct(
        private readonly AuditTrailService $auditTrailService,
    ) {
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        $this->auditTrailService->record(
            'auth',
            'logout_succeeded',
            'Office portal logout succeeded.',
            $user
        );

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
