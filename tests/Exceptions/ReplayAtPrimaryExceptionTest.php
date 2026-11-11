<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Tpetry\MultiRegion\Exceptions\ReplayAtPrimaryException;
use Tpetry\MultiRegion\Facades\Manager;
use Tpetry\MultiRegion\Middleware\MultiRegionRead;
use Tpetry\MultiRegion\Middleware\MultiRegionWrite;
use Tpetry\MultiRegion\Middleware\NoMultiRegion;
use Tpetry\MultiRegion\Testing\ManagerState;

it('is not reported when thrown in a route with the read middleware', function () {
    $log = Log::spy();
    Manager::fake(ManagerState::ReplicaCaughtUp);
    Route::get('/prices', fn () => throw new ReplayAtPrimaryException)->middleware(MultiRegionRead::class);

    $this->get('/prices');

    $log->shouldNotHaveReceived('error');
});

it('is reported when thrown in a route without the read middleware', function (array $middleware) {
    $log = Log::spy();
    Manager::fake(ManagerState::Primary);
    Route::get('/prices', fn () => throw new ReplayAtPrimaryException)->middleware($middleware);

    $this->get('/prices');

    $log->shouldHaveReceived('error')->once();
})->with([
    'write middleware' => [[MultiRegionWrite::class]],
    'no multi-region middleware' => [[NoMultiRegion::class]],
    'no middleware' => [[]],
]);

it('is reported when thrown outside of a route', function () {
    $log = Log::spy();
    report(new ReplayAtPrimaryException);

    $log->shouldHaveReceived('error')->once();
});
