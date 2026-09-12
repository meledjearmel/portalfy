<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Deux pages de connexion distinctes (admin guard "web", client guard
        // "customer"). On se base sur le guard réellement exigé par la route
        // ("auth:web" / "auth:customer"), pas sur un préfixe d'URL : /settings/*
        // est protégé par "auth:web" mais ne commence pas par "/admin" — un
        // simple `$request->is('admin*')` y aurait renvoyé un invité vers la
        // page de connexion CLIENT, un guard qui ne satisfait jamais "auth:web"
        // (boucle de redirection).
        $middleware->redirectGuestsTo(function (Request $request): string {
            $authMiddleware = collect($request->route()?->gatherMiddleware() ?? [])
                ->first(fn (string $middleware): bool => str_starts_with($middleware, 'auth:'));

            $guard = $authMiddleware ? substr($authMiddleware, 5) : 'web';

            return $guard === 'customer' ? route('login') : route('admin.login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
