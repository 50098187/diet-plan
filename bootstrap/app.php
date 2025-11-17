<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withSchedule(function ($schedule): void {
        // Update Woolworths prices daily at 2:00 AM
        $schedule->command('foods:update-prices --source=woolworths --method=scraper')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->runInBackground();

        // Update Checkers prices daily at 3:00 AM (with browser automation if available)
        $schedule->command('foods:update-prices --source=checkers --method=dusk')
            ->dailyAt('03:00')
            ->withoutOverlapping()
            ->runInBackground();

        // Update from crowd-sourced data every 6 hours
        $schedule->command('foods:update-from-crowdsource')
            ->everySixHours()
            ->withoutOverlapping();

        // Weekly forced update (bypass cache) - Sundays at 1:00 AM
        $schedule->command('foods:update-prices --source=all --method=scraper --force')
            ->weekly()
            ->sundays()
            ->at('01:00')
            ->withoutOverlapping()
            ->runInBackground();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
