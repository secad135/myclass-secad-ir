<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Dotenv\Dotenv;

// ۱. بارگذاری فایل پایه .env
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

// ۲. بررسی محیط و بارگذاری فایل اختصاصی (مثلاً .env.local یا .env.production)
$env = $_ENV['APP_ENV'] ?? 'production';
$envFile = '.env.' . $env;

if (file_exists(__DIR__ . '/../' . $envFile)) {
    Dotenv::createImmutable(__DIR__ . '/..', $envFile)->load();
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin'            => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'student'          => \App\Http\Middleware\EnsureStudent::class,
            'password.changed' => \App\Http\Middleware\EnsurePasswordChanged::class,
            'manager'          => \App\Http\Middleware\EnsureUserIsSchoolManager::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn(Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
