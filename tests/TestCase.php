<?php

namespace JeffersonGoncalves\PwaServiceWorker\Tests;

use JeffersonGoncalves\PwaServiceWorker\PwaServiceWorkerServiceProvider;
use JeffersonGoncalves\PwaServiceWorker\Tests\Fixtures\AddTestHeaderMiddleware;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            PwaServiceWorkerServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $configPath = __DIR__.'/../config/pwa-service-worker.php';

        if (file_exists($configPath)) {
            $app['config']->set('pwa-service-worker', require $configPath);
        }

        // Set before the provider boots so the route picks it up — proves the
        // configurable route middleware is wired (asserted in ServiceWorkerTest).
        $app['config']->set('pwa-service-worker.middleware', [AddTestHeaderMiddleware::class]);
    }
}
