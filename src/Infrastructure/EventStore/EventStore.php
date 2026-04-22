<?php

declare(strict_types=1);

namespace App\Infrastructure\EventStore;

use App\Domain\Event\DomainEvent;
use App\Domain\ValueObject\Id;

interface EventStore
{
    /** @return DomainEvent[] */
    public function load(Id $streamId): array;

    /**
     * Append events to a stream.
     *
     * @param DomainEvent[] $events
     *
     * @throws ConcurrencyException when another writer beat us to $expectedVersion + 1
     */
    public function append(Id $streamId, int $expectedVersion, array $events): void;

    /**
     * Stream every persisted event in global sequence order. Used to rebuild projections.
     *
     * @return iterable<RecordedEvent>
     */
    public function loadAll(): iterable;
}