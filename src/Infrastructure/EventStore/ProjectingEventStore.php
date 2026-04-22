<?php

declare(strict_types=1);

namespace App\Infrastructure\EventStore;

use App\Application\Projection\ProjectorRegistry;
use App\Domain\ValueObject\Id;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Generator;

/**
 * Wraps an EventStore so that every appended event is projected synchronously
 * inside the same DB transaction as the event insert. Trades throughput for
 * read-after-write consistency on the read models.
 */
final readonly class ProjectingEventStore implements EventStore
{
    public function __construct(
        private EventStore $inner,
        private Connection $connection,
        private ProjectorRegistry $projectors,
    ) {
    }

    public function load(Id $streamId): array
    {
        return $this->inner->load($streamId);
    }

    public function loadAll(): Generator
    {
        yield from $this->inner->loadAll();
    }

    public function append(Id $streamId, int $expectedVersion, array $events): void
    {
        $this->connection->transactional(function () use ($streamId, $expectedVersion, $events): void {
            $this->inner->append($streamId, $expectedVersion, $events);

            $occurredAt = new DateTimeImmutable();
            foreach ($events as $event) {
                foreach ($this->projectors->all() as $projector) {
                    $projector->project($event, $occurredAt);
                }
            }
        });
    }
}