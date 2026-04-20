<?php

declare(strict_types=1);

namespace App\Domain\Event\Card;

use App\Domain\Event\DomainEvent;
use App\Domain\Event\EventType;
use App\Domain\ValueObject\ColumnId;
use App\Domain\ValueObject\Id;

final readonly class CardMoved implements DomainEvent
{
    public function __construct(
        public Id $cardId,
        public ColumnId $fromColumnId,
        public ColumnId $toColumnId,
    ) {
    }

    public function aggregateId(): Id
    {
        return $this->cardId;
    }

    public function toPayload(): array
    {
        return [
            'from_column_id' => $this->fromColumnId->toString(),
            'to_column_id'   => $this->toColumnId->toString(),
        ];
    }

    public static function fromPayload(Id $aggregateId, array $payload): self
    {
        return new self(
            cardId:       $aggregateId,
            fromColumnId: ColumnId::fromString($payload['from_column_id']),
            toColumnId:   ColumnId::fromString($payload['to_column_id']),
        );
    }

    public static function eventType(): EventType
    {
        return EventType::CardMoved;
    }
}