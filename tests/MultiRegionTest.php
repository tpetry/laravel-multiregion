<?php

declare(strict_types=1);

use Tpetry\MultiRegion\Exceptions\ReplayAtPrimaryException;
use Tpetry\MultiRegion\Facades\Manager;
use Tpetry\MultiRegion\MultiRegion;
use Tpetry\MultiRegion\Testing\ManagerState;

describe('requirePrimary()', function () {
    it('continues in the primary region', function () {
        Manager::fake(ManagerState::Primary);

        MultiRegion::requirePrimary();

        Manager::assertReplayedAtPrimary(false);
    });

    it('throws a replay at the primary region in a replica region', function () {
        Manager::fake(ManagerState::ReplicaCaughtUp);

        expect(fn () => MultiRegion::requirePrimary())->toThrow(ReplayAtPrimaryException::class);
    });
});
