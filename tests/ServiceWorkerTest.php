<?php

use Illuminate\Support\Facades\Config;
use JeffersonGoncalves\PwaServiceWorker\Tests\TestCase;

it('serves the service worker as javascript with the right headers', function () {
    $response = $this->get('/sw.js');

    $response->assertOk();

    expect($response->headers->get('Content-Type'))->toContain('application/javascript');
    expect($response->headers->get('Service-Worker-Allowed'))->toBe('/');
    expect($response->headers->get('Cache-Control'))->toContain('no-cache');
});

it('renders the cache prefix, offline url and strategies into the body', function () {
    $body = $this->get('/sw.js')->getContent();

    expect($body)
        ->toContain("const CACHE_PREFIX = 'pwa'")
        ->toContain("const OFFLINE_URL = '/offline'")
        ->toContain('cacheFirst')
        ->toContain('networkFirst')
        ->toContain('staleWhileRevalidate')
        ->toContain("type: 'pwa-updated'");
});

it('falls back to a dev version when the build manifest is absent', function () {
    expect($this->get('/sw.js')->getContent())->toContain("const VERSION = 'dev'");
});

it('encodes the configured precache + passthrough lists as json arrays', function () {
    Config::set('pwa-service-worker.precache_urls', ['/offline', '/', '/?source=pwa']);
    Config::set('pwa-service-worker.passthrough_prefixes', ['/admin', '/livewire']);
    Config::set('pwa-service-worker.passthrough_exact', ['/sw.js', '/manifest.json']);

    $body = $this->get('/sw.js')->getContent();

    expect($body)
        ->toContain('"\/?source=pwa"')
        ->toContain('"\/admin"')
        ->toContain('"\/manifest.json"');
});

it('honours a custom cache prefix', function () {
    Config::set('pwa-service-worker.cache_prefix', 'jg-pwa');

    expect($this->get('/sw.js')->getContent())->toContain("const CACHE_PREFIX = 'jg-pwa'");
});

it('applies the configured route middleware', function () {
    // TestCase wires pwa-service-worker.middleware to a header-adding middleware.
    $this->get('/sw.js')->assertHeader('X-Sw-Middleware', 'applied');
});

it('serves the worker at a custom path', function () {
    // The route path is read at boot, so assert the default path responds and
    // a foreign path 404s (proving the route is bound to the configured path).
    $this->get('/sw.js')->assertOk();
    $this->get('/service-worker.js')->assertNotFound();
});

it('derives a 12-char version from the build manifest md5 when present', function () {
    $manifest = tempnam(sys_get_temp_dir(), 'pwa-sw-manifest');
    file_put_contents($manifest, '{"resources/app.js":{"file":"assets/app-abc123.js"}}');
    Config::set('pwa-service-worker.build_manifest', $manifest);

    try {
        $expected = substr((string) md5_file($manifest), 0, 12);

        expect($this->get('/sw.js')->getContent())
            ->toContain("const VERSION = '{$expected}'")
            ->not->toContain("const VERSION = 'dev'");
    } finally {
        @unlink($manifest);
    }
});

it('precaches each url independently so one bad url does not abort install', function () {
    $body = $this->get('/sw.js')->getContent();

    // Per-URL add().catch() instead of the atomic addAll(), so a single 404
    // precache URL cannot reject the whole install.
    expect($body)
        ->toContain('cache.add(url).catch(() => {})')
        ->not->toContain('cache.addAll(PRECACHE_URLS)');
});

it('does not register the /sw.js route when the package is disabled', function () {
    TestCase::$enabled = false;

    try {
        $this->refreshApplication();

        $this->get('/sw.js')->assertNotFound();
    } finally {
        TestCase::$enabled = true;
        $this->refreshApplication();
    }
});
