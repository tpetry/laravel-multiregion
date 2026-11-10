<div align="center">
    <h1>Laravel Multi Region Databases</h1>
</div>

<p align="center">
    <a href="https://packagist.org/packages/tpetry/laravel-multiregion"><img src="https://img.shields.io/packagist/v/tpetry/laravel-multiregion.svg?style=flat-square" alt="Packagist"></a>
    <a href="https://packagist.org/packages/tpetry/laravel-multiregion"><img src="https://img.shields.io/packagist/php-v/tpetry/laravel-multiregion.svg?style=flat-square" alt="PHP from Packagist"></a>
    <a href="https://packagist.org/packages/tpetry/laravel-multiregion"><img src="https://badge.laravel.cloud/badge/tpetry/laravel-multiregion?style=flat" alt="Laravel versions"></a>
    <a href="https://github.com/tpetry/laravel-multiregion/actions"><img src="https://img.shields.io/github/actions/workflow/status/tpetry/laravel-multiregion/tests.yml?branch=main&label=Tests&style=flat-square" alt="GitHub Workflow Status (main)"></a>
    <a href="https://packagist.org/packages/tpetry/laravel-multiregion"><img src="https://img.shields.io/packagist/dt/tpetry/laravel-multiregion.svg?style=flat-square" alt="Total Downloads"></a>
</p>

Middleware to create error-free multi-region applications

## Installation

You can install the package via Composer:

```bash
composer require tpetry/laravel-multiregion
```

### Publishing the Configuration File

```bash
php artisan vendor:publish --tag="multiregion-config"
```

## Usage

Every route must declare whether it reads or writes data, so replica regions know which requests they can handle and which ones must be repeated at the primary region:

```php
Route::get('/posts', [PostController::class, 'index'])->multiRegionRead();
Route::post('/posts', [PostController::class, 'store'])->multiRegionWrite();
Route::get('/up', HealthController::class)->noMultiRegion();
```

Use `noMultiRegion()` for routes that intentionally don't use multi-region handling, e.g. when they don't use the database.

### Resource routes

Resource routes get `multiRegion()`, which applies `multiRegionRead()` to the `index`, `create`, `show` and `edit` routes and `multiRegionWrite()` to the `store`, `update` and `destroy` routes:

```php
Route::resource('posts', PostController::class)->multiRegion();
Route::apiResource('comments', CommentController::class)->multiRegion();
Route::singleton('profile', ProfileController::class)->multiRegion();
```

It works with `only()` and `except()`, and replaces the multi-region middleware of a surrounding route group.

### Routes registered by the framework or packages

Routes registered by the framework or packages can't get the middleware in your route files. Configure them in the `routes` option of the `config/multiregion.php` configuration file instead:

```php
'routes' => [

    // the settings shipped with this package for popular packages
    'presets' => [
        'laravel/fortify' => true,
        'laravel/passkeys' => true,
    ],

    // routes not needing any multi-region settings, by their URL (starting with a slash) or name, both may contain wildcards
    'ignore' => [
        '/_boost/*',
        '/_debugbar/*',
        '/_inertia/*',
        '/laravel-login-link-login',
        '/storage/*',
        '/up',
        'horizon.*',
    ],

    // the mode (read, write or none) for routes selected by their name or URL (starting with a slash)
    'custom' => [
        [
            'route' => 'teams.show',
            'method' => 'GET',
            'action' => 'Laravel\Jetstream\Http\Controllers\Livewire\TeamController@show',
            'mode' => 'read',
        ],
        [
            'route' => '/user/profile',
            'method' => 'GET',
            'action' => 'Laravel\Jetstream\Http\Controllers\*',
            'mode' => 'read',
        ],
    ],

],
```

The method and action ensure the mode is applied to the correct route, even when a package update changes its routes or a route name is reused. The method is the HTTP method (e.g. `POST`), and the action is the controller and method (e.g. `Laravel\Fortify\Http\Controllers\PasswordController@update`) or, for closure routes, the file defining the closure (e.g. `vendor/laravel/boost/src/BoostServiceProvider.php`). Wildcards can be used to simplify them, e.g. `Laravel\Fortify\Http\Controllers\*`. Entries not matching any route are skipped, so presets and entries for packages that aren't installed have no effect. Custom entries replace the ones of the presets for the same route and method.

### Requiring the primary region inside a request

Some code of a read route can only run in the primary region, e.g. filling a cache that is stored in the replicated database or calling a system that is only reachable from there. `MultiRegion::requirePrimary()` does nothing in the primary region and replays the current request at the primary region when called in a replica region:

```php
use Tpetry\MultiRegion\MultiRegion;

$prices = Cache::get("prices:{$customer->id}");

if ($prices === null) {
    MultiRegion::requirePrimary();

    $prices = Cache::remember("prices:{$customer->id}", 3600, fn () => $erp->prices($customer));
}
```

### Testing that every route uses multi-region middleware

A forgotten `multiRegionRead()`, `multiRegionWrite()` or `noMultiRegion()` on a route can lead to stale data. An architecture test can catch them. Add the `ExpectsRoutes` trait to your tests in `tests/Pest.php`:

```php
use Tpetry\MultiRegion\Pest\ExpectsRoutes;

pest()->extend(TestCase::class)
    ->use(ExpectsRoutes::class)
    ->in('Feature', 'Arch');
```

And create an architecture test, e.g. `tests/Arch/MultiRegionTest.php`. It ignores the routes of the `routes.ignore` option and the presets, and more can be added with `ignoring()`:

```php
arch()
    ->expectRoutes()
    ->toUseMultiRegionMiddleware()
    ->ignoring([
        'crm.page.*',
    ]);
```

The test fails for the first route without one of the middleware.

### Listing the multi-region mode of every route

The `multiregion:routes` command lists every route with its multi-region mode: `uses multiRegionRead()` in cyan, `uses multiRegionWrite()` in magenta, `uses noMultiRegion()` in yellow, `ignored (pattern)` in gray, and `missing` in bold red. It exits with an error code when a route has no multi-region middleware, so it can also be used in CI:

```bash
php artisan multiregion:routes
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## License

Laravel Multi Region Databases is open-sourced software licensed under the [MIT license](LICENSE.md).
