<?php

declare(strict_types=1);

namespace App\Domain\Event\Card;

use App\Domain\Event\DomainEvent;
use App\Domain\Event\EventType;
use App\Domain\ValueObject\Id;

final readonly class CardMoved implements DomainEvent
{
    public function __construct(
        public Id $cardId,
        public string $fromColumnId,
        public string $toColumnId,
    ) {
    }

    public function aggregateId(): Id
    {
        return $this->cardId;
    }

    public function toPayload(): array
    {
        return [
            'from_column_id' => $this->fromColumnId,
            'to_column_id'   => $this->toColumnId,
        ];
    }

    public static function fromPayload(Id $aggregateId, array $payload): self
    {
        return new self(
            cardId:       $aggregateId,
            fromColumnId: $payload['from_column_id'],
            toColumnId:   $payload['to_column_id'],
        );
    }

    public static function eventType(): EventType
    {
        return EventType::CardMoved;
    }
}