<?php

use App\Support\PortalErrorPresenter;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

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

            $request->session()->regenerateToken();

            return redirect()->route('login', [
                'workspace' => $request->input('workspace'),
            ])
                ->withInput($request->except('_token', 'password'))
                ->with('session_expired', true)
                ->withErrors([
                    'email' => 'Your session expired. Please sign in again.',
                ]);
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            $presenter = app(PortalErrorPresenter::class);

            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
                'portal_error' => $presenter->fromMessageBag($e->validator->errors(), $request),
            ], 422);
        });

        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($e instanceof ValidationException || $e instanceof TokenMismatchException) {
                return null;
            }

            if ($e instanceof \Illuminate\Auth\AuthenticationException || $e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                return null;
            }

            if ($e instanceof HttpExceptionInterface && $e->getStatusCode() < 500) {
                return null;
            }

            if ($request->expectsJson()) {
                $presenter = app(PortalErrorPresenter::class);
                $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;

                return response()->json([
                    'message' => $status >= 500 ? 'Server error' : $e->getMessage(),
                    'portal_error' => $presenter->fromThrowable($e, $request),
                ], $status);
            }

            if (config('app.debug') || ! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
                return null;
            }

            $presenter = app(PortalErrorPresenter::class);

            return redirect()->back()
                ->withInput($request->except('password', 'password_confirmation', '_token'))
                ->with('portal_error', $presenter->fromThrowable($e, $request));
        });
    })->create();
