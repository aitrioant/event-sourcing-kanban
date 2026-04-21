<?php

declare(strict_types=1);

namespace App\Infrastructure\EventStore;

use App\Domain\Event\DomainEvent;
use App\Domain\Event\EventType;
use App\Domain\ValueObject\Id;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

final readonly class DoctrineEventStore implements EventStore
{
    public function __construct(
        private Connection $connection,
        private EventMap $eventMap,
    ) {
    }

    public function load(Id $streamId): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT event_type, payload FROM events WHERE stream_id = :id ORDER BY version ASC',
            ['id' => $streamId->toString()],
        );

        return array_map(
            fn (array $row) => $this->eventMap->classFor(EventType::from($row['event_type']))::fromPayload(
                $streamId,
                json_decode($row['payload'], true, flags: JSON_THROW_ON_ERROR),
            ),
            $rows,
        );
    }

    public function append(Id $streamId, int $expectedVersion, array $events): void
    {
        $this->connection->transactional(function (Connection $conn) use ($streamId, $expectedVersion, $events): void {
            $version = $expectedVersion;
            foreach ($events as $event) {
                $version++;
                try {
                    $conn->insert('events', [
                        'stream_id'  => $streamId->toString(),
                        'version'    => $version,
                        'event_type' => $event::eventType()->value,
                        'payload'    => json_encode($event->toPayload(), JSON_THROW_ON_ERROR),
                        'metadata'   => '{}',
                    ]);
                } catch (UniqueConstraintViolationException $e) {
                    throw new ConcurrencyException($streamId, $version, $e);
                }
            }
        });
    }
}