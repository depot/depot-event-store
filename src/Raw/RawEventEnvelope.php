<?php

namespace Depot\EventStore\Raw;

use DateTimeImmutable;
use Depot\Contract\Contract;

class RawEventEnvelope
{
    /**
     * @var Contract
     */
    private $eventType;

    /**
     * @var string
     */
    private $eventId;

    /**
     * @var object
     */
    private $event;

    /**
     * @var int
     */
    private $version;

    /**
     * @var Contract
     */
    private $metadataType;

    /**
     * @var object
     */
    private $metadata;

    /**
     * @var DateTimeImmutable
     */
    private $when;

    public function __construct(
        Contract $eventType,
        $eventId,
        $event,
        $version,
        $when,
        $metadataType = null,
        $metadata = null
    ) {
        $this->eventType = $eventType;
        $this->eventId = $eventId;
        $this->event = $event;
        $this->version = $version;
        $this->when = $when;
        $this->metadataType = $metadataType;
        $this->metadata = $metadata;
    }

    /**
     * @return Contract
     */
    public function getEventType()
    {
        return $this->eventType;
    }

    /**
     * @return string
     */
    public function getEventId()
    {
        return $this->eventId;
    }

    /**
     * @return array
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return Contract|null
     */
    public function getMetadataType()
    {
        return $this->metadataType;
    }

    /**
     * @return array|null
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * @param null $metadata
     * @return static
     */
    public function withMetadata($metadata = null)
    {
        $clone = clone($this);
        $clone->metadata = $metadata;

        return $clone;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getWhen()
    {
        return $this->when;
    }
}
