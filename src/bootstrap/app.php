<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust all proxies (nginx reverse proxy)
        $middleware->trustProxies(at: '*');
        
        // Exclude logout GET from CSRF to prevent 419 errors on expired sessions
        $middleware->validateCsrfTokens(except: [
            '/logout',
            '/webhooks/*',
        ]);
        
        $middleware->web(append: [
            \App\Http\Middleware\ResolveSubdomainTenant::class,
        ]);

        $middleware->alias([
            'platform.admin' => \App\Http\Middleware\EnsurePlatformAdministrator::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle 419 (CSRF token expired) gracefully - redirect back with message
        $exceptions->renderable(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, $request) {
            if ($e->getStatusCode() === 419) {
                return redirect()->route('login-register')
                    ->with('status', __('Your session has expired. Please try again.'));
            }
        });
    })->create();
