<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\ValidatePayMongoWebhookSignature;
use App\Models\User;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        $middleware->alias([
            'paymongo.webhook' => ValidatePayMongoWebhookSignature::class,
            'role.in' => EnsureUserHasRole::class,
        ]);

        $middleware->redirectGuestsTo(function (Request $request): string {
            return $request->is('farmer') || $request->is('farmer/*') || $request->is('api/farmer/*')
                ? route('farmer.pwa.login')
                : route('login');
        });

        $middleware->redirectUsersTo(function (Request $request): string {
            $user = $request->user();

            if (! $user) {
                return route('login');
            }

            if (in_array($user->role, [User::ROLE_ADMIN, User::ROLE_STAFF], true)) {
                return route('admin.reports.index');
            }

            return route('farmer.pwa.app');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(function (Request $request, \Throwable $exception): bool {
            $acceptsJson = str_contains((string) $request->header('Accept'), 'application/json');

            return ($request->is('farmer/*') || $request->is('api/farmer/*'))
                && ($request->expectsJson() || $request->ajax() || $acceptsJson);
        });
    })->create();
