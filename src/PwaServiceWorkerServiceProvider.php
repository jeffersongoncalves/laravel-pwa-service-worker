<?php

declare(strict_types=1);

namespace JeffersonGoncalves\PwaServiceWorker;

use Illuminate\Support\Facades\Route;
use JeffersonGoncalves\PwaServiceWorker\Http\Controllers\ServiceWorkerController;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class PwaServiceWorkerServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-pwa-service-worker')
            ->hasConfigFile()
            ->hasViews();
    }

    public function packageBooted(): void
    {
        if (! config('pwa-service-worker.enabled', true)) {
            return;
        }

        // Registered here (not in a routes file) so the path is config-driven.
        // An invokable controller keeps route:cache working in production.
        Route::get(ltrim((string) config('pwa-service-worker.path', 'sw.js'), '/'), ServiceWorkerController::class)
            ->name('pwa.service-worker');
    }
}
