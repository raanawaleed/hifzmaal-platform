<?php

use Illuminate\Database\QueryException;
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
        $middleware->alias([
            'family.access' => \App\Http\Middleware\EnsureFamilyAccess::class,
            'active' => \App\Http\Middleware\EnsureUserIsActive::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
        ]);

        $middleware->api(append: [
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\SecurityHeaders::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeaders::class,
        ]);

        $middleware->throttleApi();

        // INSTALLATION.md's deployment is nginx (or similar) reverse-proxying
        // to php-fpm on the same box. Without this, every request looks like
        // it comes from nginx's own address, so $request->ip() returns the
        // same value for every visitor — collapsing the per-IP throttles on
        // /login, /register, etc. into one shared bucket (one abusive client
        // can lock out everyone). Trusting '*' is safe here because nothing
        // but your own reverse proxy can reach php-fpm directly.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson()
        );

        // A restricted foreign key blocked the delete — surface it as a
        // validation-style error instead of a raw 500.
        $exceptions->render(function (QueryException $e, Request $request) {
            if ($request->is('api/*') && in_array($e->getCode(), ['23000', '23503'])) {
                return response()->json([
                    'message' => 'This record cannot be deleted because it is still in use.',
                    'error' => 'resource_in_use',
                ], 422);
            }
        });
    })->create();
