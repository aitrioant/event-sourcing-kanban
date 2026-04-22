<?php

declare(strict_types=1);

namespace App\Domain\Event\Card;

use App\Domain\Event\DomainEvent;
use App\Domain\Event\EventType;
use App\Domain\ValueObject\Id;

final readonly class CardCommented implements DomainEvent
{
    public function __construct(
        public Id $cardId,
        public Id $commentId,
        public Id $authorId,
        public string $body,
    ) {
    }

    public function aggregateId(): Id
    {
        return $this->cardId;
    }

    public function toPayload(): array
    {
        return [
            'comment_id' => $this->commentId->toString(),
            'author_id'  => $this->authorId->toString(),
            'body'       => $this->body,
        ];
    }

    public static function fromPayload(Id $aggregateId, array $payload): self
    {
        return new self(
            cardId:    $aggregateId,
            commentId: Id::fromString($payload['comment_id']),
            authorId:  Id::fromString($payload['author_id']),
            body:      $payload['body'],
        );
    }

    public static function eventType(): EventType
    {
        return EventType::CardCommented;
    }
}
