<?php

declare(strict_types=1);

namespace Tpetry\MultiRegion\Exceptions;

use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use RuntimeException;
use Tpetry\MultiRegion\Middleware\MultiRegionRead;

class ReplayAtPrimaryException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The request must be replayed at the primary region.');
    }

    /**
     * The router reports exceptions before the read middleware handles them. Returning true marks the exception as
     * reported, so Laravel skips its default reporting and the expected replays aren't logged as errors. Everywhere
     * else (e.g. console commands or routes without the read middleware) nothing handles it, so it is reported.
     */
    public function report(Router $router): bool
    {
        $route = request()->route();

        return $route instanceof Route && in_array(MultiRegionRead::class, $router->gatherRouteMiddleware($route), true);
    }
}
