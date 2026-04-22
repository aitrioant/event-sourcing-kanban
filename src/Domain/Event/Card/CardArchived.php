<?php

declare(strict_types=1);

namespace App\Domain\Event\Card;

use App\Domain\Event\DomainEvent;
use App\Domain\Event\EventType;
use App\Domain\ValueObject\Id;

final readonly class CardArchived implements DomainEvent
{
    public function __construct(
        public Id $cardId,
    ) {
    }

    public function aggregateId(): Id
    {
        return $this->cardId;
    }

    public function toPayload(): array
    {
        return [];
    }

    public static function fromPayload(Id $aggregateId, array $payload): self
    {
        return new self(cardId: $aggregateId);
    }

    public static function eventType(): EventType
    {
        return EventType::CardArchived;
    }
}