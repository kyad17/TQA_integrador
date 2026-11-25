<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;
use Inertia\Middleware as InertiaMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__.'/../routes/web.php', commands: __DIR__.'/../routes/console.php')
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [InertiaMiddleware::class]);
        $middleware->alias(['operador' => App\Http\Middleware\Operador::class]);
    })
    ->withExceptions(fn () => null)
    ->create();
