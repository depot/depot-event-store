<?php

namespace Depot\EventStore\Persistence\Adapter\InMemory;

use Depot\Contract\Contract;
use Depot\EventStore\EventEnvelope;
use Depot\EventStore\CommittedEventVisitor;
use Depot\EventStore\Management\Criteria;
use Depot\EventStore\Management\EventStoreManagement;
use Depot\EventStore\Persistence\CommittedEvent;
use Depot\EventStore\Persistence\OptimisticConcurrencyFailed;
use Depot\EventStore\Persistence\Persistence;
use Depot\EventStore\Serialization\Serializer;
use Depot\EventStore\Transaction\CommitId;

class InMemoryPersistence implements Persistence, EventStoreManagement
{
    /**
     * @var Serializer
     */
    private $eventSerializer;

    /**
     * @var Serializer
     */
    private $metadataSerializer;

    /**
     * @var CommittedEvent[]
     */
    private $records = [];

    public function __construct(
        Serializer $eventSerializer,
        Serializer $metadataSerializer
    ) {
        $this->eventSerializer = $eventSerializer;
        $this->metadataSerializer = $metadataSerializer;
    }

    public function fetch(Contract $aggregateRootType, $aggregateRootId)
    {
        $eventEnvelopes = [];

        foreach ($this->records as $record) {
            if ($aggregateRootType != $record->getAggregateRootType()) {
                continue;
            }

            if ($aggregateRootId != $record->getAggregateRootId()) {
                continue;
            }

            $eventEnvelopes[] = $record->getEventEnvelope();
        }

        return $eventEnvelopes;
    }

    public function visitCommittedEvents(Criteria $criteria, CommittedEventVisitor $committedEventVisitor)
    {
        foreach ($this->records as $record) {
            if (! $criteria->isMatchedBy($record)) {
                continue;
            }
            $committedEventVisitor->doWithCommittedEvent($record);
        }
    }

    /**
     * @param CommitId $commitId
     * @param Contract $aggregateRootType
     * @param string $aggregateRootId
     * @param int $expectedAggregateRootVersion
     * @param EventEnvelope[] $eventEnvelopes
     * @param \DateTimeImmutable|null $now
     */
    public function commit(
        CommitId $commitId,
        Contract $aggregateRootType,
        $aggregateRootId,
        $expectedAggregateRootVersion,
        array $eventEnvelopes,
        $now = null
    ) {
        $aggregateRootVersion = $this->versionFor($aggregateRootType, $aggregateRootId);

        if (! $now) {
            $now = new \DateTimeImmutable('now');
        }

        if ($aggregateRootVersion !== $expectedAggregateRootVersion) {
            throw new OptimisticConcurrencyFailed(
                $aggregateRootType->getContractName(),
                $aggregateRootId,
                sprintf(
                    'Expected aggregate root version %d but found %d.',
                    $expectedAggregateRootVersion,
                    $aggregateRootVersion
                )
            );
        }

        foreach ($eventEnvelopes as $eventEnvelope) {

            $record = new CommittedEvent(
                $commitId,
                $now,
                $aggregateRootType,
                $aggregateRootId,
                $aggregateRootVersion,
                $eventEnvelope
            );

            $this->records[] = $record;
        }
    }

    private function versionFor(Contract $aggregateRootType, $aggregateRootId)
    {
        $version = -1;

        foreach ($this->fetch($aggregateRootType, $aggregateRootId) as $eventEnvelope) {
            if ($eventEnvelope->getVersion() > $version) {
                $version = $eventEnvelope->getVersion();
            }
        }

        return $version;
    }
}
