<?php

declare(strict_types=1);

namespace App\Domain\Event\Card;

use App\Domain\Event\DomainEvent;
use App\Domain\Event\EventType;
use App\Domain\ValueObject\Id;

final readonly class CardCreated implements DomainEvent
{
    public function __construct(
        public Id $cardId,
        public Id $boardId,
        public string $columnId,
        public string $title,
    ) {
    }

    public function aggregateId(): Id
    {
        return $this->cardId;
    }

    public function toPayload(): array
    {
        return [
            'board_id'  => $this->boardId->toString(),
            'column_id' => $this->columnId,
            'title'     => $this->title,
        ];
    }

    public static function fromPayload(Id $aggregateId, array $payload): self
    {
        return new self(
            cardId:   $aggregateId,
            boardId:  Id::fromString($payload['board_id']),
            columnId: $payload['column_id'],
            title:    $payload['title'],
        );
    }

    public static function eventType(): EventType
    {
        return EventType::CardCreated;
    }
}