<?php

use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [SetLocale::class, SecurityHeaders::class]);
        $middleware->alias(['role' => EnsureRole::class]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Never leak stack traces / SQL to users (APP_DEBUG=false in production).
    })->create();

/*
SYSTEM DESIGN:
Laravel 11/12 application bootstrap: registers global web middleware and the `role` alias.

DATA STRUCTURE:
Middleware pipeline (ordered list / onion model).
*/
