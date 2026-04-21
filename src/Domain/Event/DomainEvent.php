<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\ValueObject\Id;

interface DomainEvent
{
    public function aggregateId(): Id;

    public function toPayload(): array;

    public static function fromPayload(Id $aggregateId, array $payload): self;

    public static function eventType(): EventType;
}