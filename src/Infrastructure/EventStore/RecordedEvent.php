<?php

declare(strict_types=1);

namespace App\Infrastructure\EventStore;

use App\Domain\Event\DomainEvent;
use DateTimeImmutable;

final readonly class RecordedEvent
{
    public function __construct(
        public DomainEvent $event,
        public DateTimeImmutable $occurredAt,
    ) {
    }
}