<?php

declare(strict_types=1);

use Illuminate\Http\Exceptions\HttpResponseException;
use Tpetry\MultiRegion\Facades\Manager;
use Tpetry\MultiRegion\MultiRegion;
use Tpetry\MultiRegion\Testing\ManagerState;

describe('requirePrimary()', function () {
    it('continues in the primary region', function () {
        Manager::fake(ManagerState::Primary);

        MultiRegion::requirePrimary();

        Manager::assertReplayedAtPrimary(false);
    });

    it('replays the request at the primary region in a replica region', function () {
        Manager::fake(ManagerState::ReplicaCaughtUp, $response = response('replayed'));

        expect(fn () => MultiRegion::requirePrimary())
            ->toThrow(fn (HttpResponseException $exception) => expect($exception->getResponse())->toBe($response));

        Manager::assertReplayedAtPrimary(true);
        Manager::assertServedByHeaderAdded();
    });
});
