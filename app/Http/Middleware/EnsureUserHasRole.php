<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, $roles, true)) {
            if ($user && $this->shouldReturnToPreviousPage($request)) {
                return redirect()
                    ->to($this->safeReturnUrl($request))
                    ->with('error', 'You do not have permission to access that page.');
            }

            abort(403);
        }

        return $next($request);
    }

    private function shouldReturnToPreviousPage(Request $request): bool
    {
        return in_array($request->method(), ['GET', 'HEAD'], true)
            && ! $request->expectsJson();
    }

    private function safeReturnUrl(Request $request): string
    {
        $previousUrl = url()->previous();
        $previousHost = parse_url($previousUrl, PHP_URL_HOST);
        $previousPath = (string) parse_url($previousUrl, PHP_URL_PATH);
        $isSameApplication = $previousHost === null || $previousHost === $request->getHost();
        $isAdminPage = str_starts_with($previousPath, '/admin');
        $isDifferentPage = rtrim($previousUrl, '/') !== rtrim($request->fullUrl(), '/');

        if ($isSameApplication && $isAdminPage && $isDifferentPage) {
            return $previousUrl;
        }

        return route('admin.dashboard.index');
    }
}
