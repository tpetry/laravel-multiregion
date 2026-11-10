<?php

declare(strict_types=1);

namespace Tpetry\MultiRegion;

use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\PostgresConnection;
use Illuminate\Http\Exceptions\HttpResponseException;
use Tpetry\MultiRegion\Facades\CommitId;
use Tpetry\MultiRegion\Facades\Manager;

class MultiRegion
{
    /**
     * Determine if this instance is part of the primary region.
     */
    public static function isPrimary(): bool
    {
        return Manager::isPrimary();
    }

    /**
     * Determine if the replica region has received the last write operation of the user.
     */
    public static function hasCaughtUp(): bool
    {
        return Manager::hasCaughtUp();
    }

    /**
     * Replay the current request at the primary region when this instance is not part of it.
     *
     * @throws HttpResponseException
     */
    public static function requirePrimary(): void
    {
        if (Manager::isPrimary()) {
            return;
        }

        throw new HttpResponseException(Manager::addServedByHeader(Manager::replayAtPrimary()));
    }

    /**
     * Measure the replication lag between the primary and the replica database in milliseconds, or null when the data
     * hasn't been replicated within the waiting time.
     */
    public static function measureLag(Connection $primary, Connection $replica, int $wait): ?int
    {
        /** @var class-string<Factory<Model>> $factory */
        $factory = config()->string('multiregion.monitoring.model.factory');

        $model = $factory::new()->makeOne();
        $model->setConnection($primary->getName())->save();
        $start = hrtime(true);

        $commitId = CommitId::getLastCommitId($primary);
        if ($replica instanceof PostgresConnection && version_compare($replica->getServerVersion(), '19', '<')) {
            // PostgreSQL before 19 can't wait for a commit ID, so it is checked repeatedly with a pause in between.
            while (! CommitId::hasCommitId($replica, $commitId)) {
                if (hrtime(true) - $start >= $wait * 1_000_000_000) {
                    return null;
                }

                usleep(config()->integer('multiregion.monitoring.sleep_milliseconds') * 1_000);
            }
        } elseif (! CommitId::hasCommitId($replica, $commitId, $wait)) {
            return null;
        }

        $lag = intdiv(hrtime(true) - $start, 1_000_000);
        $model->update(['delay' => $lag]);

        return $lag;
    }
}
