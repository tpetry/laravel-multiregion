# Release Notes

## [v4.2.0](https://github.com/tpetry/laravel-multiregion/compare/4.1.0...4.2.0) - 2026-11-11

### Added

- `MultiRegion::requirePrimary()` to force replay a request at the primary region when currently in a replica region.

## [v4.1.0](https://github.com/tpetry/laravel-multiregion/compare/4.0.0...4.1.0) - 2026-11-09

### Added

- Monitoring of the replication lag between the primary and the replica database:
  - `MultiRegion::measureLag()` for building manual logic and optional health check URL with automatic checks
  - Every measurement is stored in a table for later analysis with support for custom attributes
  - `multiregion:prune` artisan command to delete old measurements, e.g. `multiregion:prune mysql_primary --days=7`.

## [v4.0.0](https://github.com/tpetry/laravel-multiregion/compare/3.4.0...4.0.0) - 2026-11-09

### Added

- Multi-region settings for routes registered by the framework or packages, configured with presets and custom routes.
- Added `/laravel-login-link-login` (`spatie/laravel-login-link`) to the default ignored routes.

### Breaking Changes

- The configuration file has changed. Republish it or update your copy:
  - `ignore` moved to `routes.ignore`.
  - `routes.presets` and `routes.custom` are new.

## [v3.4.0](https://github.com/tpetry/laravel-multiregion/compare/3.3.0...3.4.0) - 2026-11-09

### Added

- `multiRegion()` route modifier for resource routes (`Route::resource()`, `Route::apiResource()`, `Route::singleton()` and `Route::apiSingleton()`).
- Added `/_debugbar/*` to the default ignored routes.

### Changed

- `multiregion:routes` shows read and write routes in different colors to easily distinguish them.

## [v3.3.0](https://github.com/tpetry/laravel-multiregion/compare/3.2.0...3.3.0) - 2026-11-09

### Added

- `multiregion:routes` artisan command to list every route with its multi-region mode.
- `ignore` configuration option for routes (used by Pest architecture tests and `multiregion:routes`).

### Changed

- Bumped minimum Laravel dependency from 13.0 to 13.40 as the commit IDs of MySQL, MariaDB and PostgreSQL are now provided by Laravel itself.

## [v3.2.0](https://github.com/tpetry/laravel-multiregion/compare/3.1.0...3.2.0) - 2026-11-06

### Added

- `noop` driver to disable multi-region handling for local development and testing.

## [v3.1.0](https://github.com/tpetry/laravel-multiregion/compare/3.0.0...3.1.0) - 2026-11-05

### Added

- A header identifying the region and/or machine that answered the request (e.g. `X-MultiRegion-ServedBy: us-east-server0`).

## [v3.0.0](https://github.com/tpetry/laravel-multiregion/compare/2.1.0...3.0.0) - 2026-11-05

### Added

- Multiple drivers defining how a request is repeated at the primary region when a replica region can't handle it:
  - `redirect`: Redirects the user to the primary region (the behavior of v2).
  - `header`: Responds with a header (e.g. `X-MultiRegion-Replay: https://primary.example.com/posts`) used by e.g. a Cloudflare Worker to repeat the request.
  - `fly`: Responds with Fly.io's `fly-replay` header, so Fly's proxy replays the request at the specified target.
  
### Breaking Changes

- The configuration file has changed. Republish it or update your copy:
  - `driver` (`MULTIREGION_DRIVER`) is new and required in every region. It has no default, so every application must set it.
  - `url.read` and `url.write` moved to `drivers.redirect.url.read` and `drivers.redirect.url.write`.
  - `MULTIREGION_URL_READ` and `MULTIREGION_URL_WRITE` have been renamed to `MULTIREGION_REDIRECT_URL_READ` and `MULTIREGION_REDIRECT_URL_WRITE`.

#### Upgrading the environment variables

To keep the behavior of v2, use the `redirect` driver:

```diff
 MULTIREGION_PRIMARY=false
-MULTIREGION_URL_WRITE=https://primary.example.com
-MULTIREGION_URL_READ=https://example.com
+MULTIREGION_DRIVER=redirect
+MULTIREGION_REDIRECT_URL_WRITE=https://primary.example.com
+MULTIREGION_REDIRECT_URL_READ=https://example.com
```

## [v2.1.0](https://github.com/tpetry/laravel-multiregion/compare/2.0.0...2.1.0) - 2026-11-04

### Added

- Pest architecture expectation to ensure every route uses multi-region middleware.

## [v2.0.0](https://github.com/tpetry/laravel-multiregion/compare/1.0.0...2.0.0) - 2026-11-02

### Added

- Route modifiers `->multiRegionRead()`, `->multiRegionWrite()` and `->noMultiRegion()` to apply the multi-region middleware to a route.
- `NoMultiRegion` middleware to mark routes that intentionally don't use multi-region handling, e.g. health checks or routes without database access.
- URLs generated with `route()` now point automatically to the multi-region endpoint that handles them.

### Breaking Changes

- The configuration file has changed. Republish it or update your copy:
  - `primary_url` has been removed. Whether an instance is part of the primary region was derived from it being empty, it now has to be set explicitly with the new `primary` option.
  - `primary` (`MULTIREGION_PRIMARY`) must be set to `true` in the primary region and to `false` in every replica region.
  - `url.write` (`MULTIREGION_URL_WRITE`) replaces `primary_url` and must lead to the primary region. It is now needed in every region, including the primary one, to generate URLs for write routes.
  - `url.read` (`MULTIREGION_URL_READ`) is new and is used to generate URLs for read routes. It can lead to any region (including the primary region), e.g. a load balancer sending users to their nearest region.

## [v1.0.0](https://github.com/tpetry/laravel-multiregion/compare/...v1.0.0) - 2026-10-28

Initial release.
