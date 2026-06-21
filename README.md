<div class="filament-hidden">

![Laravel PWA Service Worker](https://raw.githubusercontent.com/jeffersongoncalves/laravel-pwa-service-worker/master/art/jeffersongoncalves-laravel-pwa-service-worker.png)

</div>

# Laravel PWA Service Worker

[![Latest Version on Packagist](https://img.shields.io/packagist/v/jeffersongoncalves/laravel-pwa-service-worker.svg?style=flat-square)](https://packagist.org/packages/jeffersongoncalves/laravel-pwa-service-worker)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/jeffersongoncalves/laravel-pwa-service-worker/run-tests.yml?branch=master&label=tests&style=flat-square)](https://github.com/jeffersongoncalves/laravel-pwa-service-worker/actions?query=workflow%3Arun-tests+branch%3Amaster)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/jeffersongoncalves/laravel-pwa-service-worker/fix-php-code-style-issues.yml?branch=master&label=code%20style&style=flat-square)](https://github.com/jeffersongoncalves/laravel-pwa-service-worker/actions?query=workflow%3A"Fix+PHP+code+styling"+branch%3Amaster)
[![Total Downloads](https://img.shields.io/packagist/dt/jeffersongoncalves/laravel-pwa-service-worker.svg?style=flat-square)](https://packagist.org/packages/jeffersongoncalves/laravel-pwa-service-worker)

Serve a production-ready PWA service worker at `/sw.js`, rendered from a Blade template whose cache version tracks the Vite build manifest hash — so a `npm run build` automatically busts the SW cache without you editing the worker.

Ships three caching strategies out of the box:

- **cache-first** for content-hashed `/build/*` assets (immutable, safe to cache forever)
- **network-first** for HTML navigations (fresh when online, cached + offline fallback when not)
- **stale-while-revalidate** for everything else (images, fonts, …)

…plus precache on install, old-cache teardown on activate, and a `pwa-updated` `postMessage` so your front-end can show an "update available" toast.

## Installation

```bash
composer require jeffersongoncalves/laravel-pwa-service-worker
```

The `/sw.js` route is registered automatically. Register the service worker from your layout:

```html
<script>
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js');
  }
</script>
```

Optionally publish the config and view:

```bash
php artisan vendor:publish --tag="pwa-service-worker-config"
php artisan vendor:publish --tag="pwa-service-worker-views"
```

## Offline fallback

The worker serves `config('pwa-service-worker.offline_url')` (default `/offline`) for a failed navigation when nothing is cached. **You** register that route — it is intentionally not provided, since the offline page is app-specific:

```php
Route::view('/offline', 'offline')->name('pwa.offline');
```

## Configuration

| Key | Default | Description |
| --- | --- | --- |
| `enabled` | `true` | Register the `/sw.js` route. |
| `path` | `sw.js` | Route path (keep at root for a `/` scope). |
| `view` | `pwa-service-worker::sw` | Blade view rendered as the worker body. |
| `build_manifest` | `public/build/manifest.json` | md5 seeds the cache version. |
| `cache_prefix` | `pwa` | Cache name is `{prefix}-v{version}`. |
| `offline_url` | `/offline` | Fallback for failed navigations. |
| `precache_urls` | `['/offline', '/']` | Seeded on install. |
| `passthrough_prefixes` | `['/admin', '/livewire', '/api', …]` | Always hit the network. |
| `passthrough_exact` | `['/sw.js', '/manifest.json']` | Always hit the network (exact path). |
| `asset_prefix` | `/build/` | Content-hashed assets served cache-first. |

## Testing

```bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
