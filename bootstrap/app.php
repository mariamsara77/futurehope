<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        /*
        |--------------------------------------------------------------------------
        | Proxy / Web middleware
        |--------------------------------------------------------------------------
        |
        | Keep the existing proxy behavior and web middleware configuration.
        |
        */
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            // Add web-only application middleware here when required.
        ]);

        /*
        |--------------------------------------------------------------------------
        | Spatie Laravel Permission aliases
        |--------------------------------------------------------------------------
        |
        | These aliases are used by routes such as:
        | permission:work-vote
        | permission:biodata-manage
        | role:admin
        |
        */
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool =>
                $request->is('api/*') || $request->expectsJson(),
        );
    })
    ->create();
