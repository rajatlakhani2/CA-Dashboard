<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function () {
            require base_path('routes/webhooks.php');
        },
    )
    ->withCommands([
        __DIR__ . '/../app/Console/Commands',
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            \App\Http\Middleware\EnforceSessionIdle::class,
        ]);

        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'module' => \App\Http\Middleware\CheckModuleAccess::class,
            'system.dangerous' => \App\Http\Middleware\RestrictDangerousSystemActions::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (TokenMismatchException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your session expired. Refresh the page and try again.',
                ], 419);
            }

            return redirect()->route('login')
                ->withInput($request->except('_token', 'password'))
                ->withErrors([
                    'email' => 'Your session expired. Please refresh the page and sign in again.',
                ]);
        });
    })->create();
