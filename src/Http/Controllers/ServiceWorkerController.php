<?php

declare(strict_types=1);

namespace JeffersonGoncalves\PwaServiceWorker\Http\Controllers;

use Illuminate\Http\Response;

class ServiceWorkerController
{
    /**
     * Serve the service worker from a Blade view so the cache version changes
     * whenever assets are rebuilt — the version is derived from the Vite build
     * manifest hash, without rebuilding the SW by hand. `Service-Worker-Allowed`
     * lets the browser accept the registration at the site root, and
     * `Cache-Control: no-cache` makes browsers re-validate the SW on every
     * navigation so the update path is deterministic when assets change.
     */
    public function __invoke(): Response
    {
        $manifest = (string) config('pwa-service-worker.build_manifest', public_path('build/manifest.json'));
        $buildId = is_file($manifest) ? substr((string) md5_file($manifest), 0, 12) : 'dev';

        $content = view(config('pwa-service-worker.view', 'pwa-service-worker::sw'), [
            'version' => $buildId,
            'cachePrefix' => (string) config('pwa-service-worker.cache_prefix', 'pwa'),
            'offlineUrl' => (string) config('pwa-service-worker.offline_url', '/offline'),
            'precacheUrls' => (array) config('pwa-service-worker.precache_urls', ['/offline', '/']),
            'passthroughPrefixes' => (array) config('pwa-service-worker.passthrough_prefixes', ['/admin', '/livewire', '/api']),
            'passthroughExact' => (array) config('pwa-service-worker.passthrough_exact', []),
            'assetPrefix' => (string) config('pwa-service-worker.asset_prefix', '/build/'),
        ])->render();

        return response($content, 200)
            ->header('Content-Type', 'application/javascript; charset=utf-8')
            ->header('Service-Worker-Allowed', '/')
            ->header('Cache-Control', 'no-cache, max-age=0, must-revalidate');
    }
}
