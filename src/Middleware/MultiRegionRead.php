<?php

declare(strict_types=1);

namespace Tpetry\MultiRegion\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tpetry\MultiRegion\Exceptions\ReplayAtPrimaryException;
use Tpetry\MultiRegion\Facades\Manager;

class MultiRegionRead
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        Manager::cleanupOldCommitIds();

        if (! Manager::isPrimary() && ! Manager::hasCaughtUp()) {
            return Manager::addServedByHeader(Manager::replayAtPrimary());
        }

        $response = $next($request);
        if (($response->exception ?? null) instanceof ReplayAtPrimaryException) {
            return Manager::addServedByHeader(Manager::replayAtPrimary());
        }

        return Manager::addServedByHeader($response);
    }
}
