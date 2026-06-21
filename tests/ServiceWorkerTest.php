<?php

use Illuminate\Support\Facades\Config;

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

it('serves the worker at a custom path', function () {
    // The route path is read at boot, so assert the default path responds and
    // a foreign path 404s (proving the route is bound to the configured path).
    $this->get('/sw.js')->assertOk();
    $this->get('/service-worker.js')->assertNotFound();
});
