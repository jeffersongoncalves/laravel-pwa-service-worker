<?php

namespace JeffersonGoncalves\PwaServiceWorker\Tests;

use JeffersonGoncalves\PwaServiceWorker\PwaServiceWorkerServiceProvider;
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
    }
}
