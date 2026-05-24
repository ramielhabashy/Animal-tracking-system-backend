<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use App\Http\Middleware\CheckSubscriptionLimits;
use App\Http\Middleware\CheckFeatureAccess;
use App\Http\Middleware\CustomAuthenticate;
use App\Providers\DeviceServiceProvider;
use App\Providers\HorizonServiceProvider;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        DeviceServiceProvider::class,
        HorizonServiceProvider::class,
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'limits' => CheckSubscriptionLimits::class,
            'feature' => CheckFeatureAccess::class,
            'auth' => CustomAuthenticate::class,
            'role' => \App\Http\Middleware\CheckRole::class,
            'encrypt_cookies' => \App\Http\Middleware\EncryptCookies::class,
        ]);
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        if (app()->environment('testing')) {
            return;
        }

        $exceptions->report(function (Throwable $e) {
            if (app()->environment('testing')) {
                return;
            }

            // Flare (secondary error monitor, gated by FLARE_ENABLED)
            if (env('FLARE_ENABLED', false) && env('FLARE_KEY')) {
                try {
                    $flare = \Spatie\FlareClient\Flare::make(env('FLARE_KEY'));
                    if ($githubToken = env('FLARE_GITHUB_TOKEN')) {
                        $flare->setGitHubAuth($githubToken);
                    }
                    $flare->reportError(
                        get_class($e),
                        $e->getMessage(),
                        $e->getFile(),
                        $e->getLine(),
                        $e->getTraceAsString()
                    );
                } catch (\Throwable $flareError) {
                    // Flare reporting failed silently
                }
            }
        });
    })->create();