<?php

namespace Depot\EventStore\Persistence;

use Depot\Contract\Contract;
use Depot\EventStore\EventEnvelope;
use Depot\EventStore\Transaction\CommitId;

interface Persistence
{
    /**
     * @param Contract $aggregateRootType
     * @param string $aggregateRootId
     * @return EventEnvelope[]
     */
    public function fetch(Contract $aggregateRootType, $aggregateRootId);

    /**
     * @param CommitId $commitId
     * @param Contract $aggregateRootType
     * @param $aggregateRootId
     * @param $expectedAggregateRootVersion
     * @param EventEnvelope[] $eventEnvelopes
     * @param null $now
     * @return mixed
     */
    public function commit(
        CommitId $commitId,
        Contract $aggregateRootType,
        $aggregateRootId,
        $expectedAggregateRootVersion,
        array $eventEnvelopes,
        $now = null
    );
}
