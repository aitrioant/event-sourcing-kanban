<?php

declare(strict_types=1);

namespace App\Application\Projection;

use App\Infrastructure\EventStore\EventStore;
use Doctrine\DBAL\Connection;

final readonly class ProjectionRunner
{
    public function __construct(
        private EventStore $events,
        private Connection $connection,
        private ProjectorRegistry $projectors,
    ) {
    }

    public function rebuild(string $name): int
    {
        return $this->rebuildAll([$this->projectors->get($name)]);
    }

    public function rebuildAll(?array $projectors = null): int
    {
        $projectors = $projectors ?? $this->projectors->all();

        return $this->connection->transactional(function () use ($projectors): int {
            foreach ($projectors as $projector) {
                $projector->reset();
            }

            $count = 0;
            foreach ($this->events->loadAll() as $recorded) {
                foreach ($projectors as $projector) {
                    $projector->project($recorded->event, $recorded->occurredAt);
                }
                $count++;
            }

            return $count;
        });
    }
}