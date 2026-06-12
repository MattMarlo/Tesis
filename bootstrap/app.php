<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
   // ->withMiddleware(function (Middleware $middleware): void {
        //
   // })
    ->withMiddleware(function (Middleware $middleware) {
        // Añade esta regla para ignorar el token de seguridad en la ruta de n8n
        $middleware->validateCsrfTokens(except: [
            'prereservas/webhook'
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
