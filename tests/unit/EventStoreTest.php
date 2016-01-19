<?php

use Depot\EventStore\EventStore;
use Depot\EventStore\EventEnvelope;
use Depot\EventStore\Persistence\Persistence;
use Depot\Testing\Fixtures\Banking\Account\AccountWasOpened;
use Depot\Testing\Fixtures\Banking\Account\AccountBalanceIncreased;
use Depot\Contract\SimplePhpFqcnContractResolver;
use PHPUnit_Framework_TestCase as TestCase;

class EventStoreTest extends TestCase
{
    /**
     * @var ContractResolver
     */
    private $contractResolver;

    private function setUpContractResolver()
    {
        $this->contractResolver = new SimplePhpFqcnContractResolver();
    }

    private function createEventEnvelope($aggregateRootType, $aggregateRootId, $eventId, $event, $version)
    {
        return new EventEnvelope(
            $aggregateRootType,
            $aggregateRootId,
            $this->contractResolver->resolveFromObject($event),
            $eventId,
            $event,
            $version
        );
    }

    public function testAppendingEventEnvelopeToCreatedEventStore()
    {
        $this->setUpContractResolver();

        $contract = $this->contractResolver->resolveFromClassName(Account::class);
        $aggregateRootId = 123;
        $eventId = 456;

        $persistence = $this->getMockBuilder(Persistence::class)
            ->getMock();

        $persistence
            ->expects($this->never())
            ->method('fetch');

        $eventStore = new EventStore($persistence);

        $eventStream = $eventStore->create($contract, $aggregateRootId);

        $appendedEventEnvelope = $this->createEventEnvelope(
            $contract,
            $aggregateRootId,
            $eventId,
            new AccountWasOpened('fixture-account-000', 25),
            0
        );

        $eventStream->append($appendedEventEnvelope);

        $this->assertEquals($eventStream->all(), [
            $appendedEventEnvelope
        ]);
    }

    public function testAppendingEventEnvelopeToOpenedEventStore()
    {
        $this->setUpContractResolver();

        $contract = $this->contractResolver->resolveFromClassName(Account::class);
        $aggregateRootId = 123;
        $eventId = 456;
        $secondEventId = 789;

        $existingEventEnvelope = $this->createEventEnvelope(
            $contract,
            $aggregateRootId,
            $eventId,
            new AccountWasOpened('fixture-account-000', 25),
            0
        );

        $persistence = $this->getMockBuilder(Persistence::class)
            ->getMock();

        $persistence
            ->expects($this->once())
            ->method('fetch')
            ->with($this->equalTo($contract), $this->equalTo($aggregateRootId))
            ->will($this->returnValue([$existingEventEnvelope]));

        $eventStore = new EventStore($persistence);

        $eventStream = $eventStore->open($contract, $aggregateRootId);

        $appendedEventEnvelope = $this->createEventEnvelope(
            $contract,
            $aggregateRootId,
            $secondEventId,
            new AccountBalanceIncreased('fixture-account-000', 10),
            1
        );

        $eventStream->append($appendedEventEnvelope);

        $this->assertEquals($eventStream->all(), [
            $existingEventEnvelope,
            $appendedEventEnvelope
        ]);
    }
}
