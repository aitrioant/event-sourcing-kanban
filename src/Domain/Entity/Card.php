<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Event\Card\CardCreated;
use App\Domain\Event\Card\CardMoved;
use App\Domain\Event\DomainEvent;
use App\Domain\ValueObject\Id;

final class Card extends AggregateRoot
{
    private Id $boardId;
    private string $columnId;
    private string $title;

    public static function create(Id $cardId, Id $boardId, string $columnId, string $title): self
    {
        $card = new self();
        $card->recordThat(new CardCreated($cardId, $boardId, $columnId, $title));

        return $card;
    }

    public function moveTo(string $columnId): void
    {
        if ($columnId === $this->columnId) {
            return;
        }

        $this->recordThat(new CardMoved($this->id, $this->columnId, $columnId));
    }

    public function columnId(): string
    {
        return $this->columnId;
    }

    public function title(): string
    {
        return $this->title;
    }

    protected function apply(DomainEvent $event): void
    {
        match (true) {
            $event instanceof CardCreated => $this->applyCardCreated($event),
            $event instanceof CardMoved   => $this->applyCardMoved($event),
        };
    }

    private function applyCardCreated(CardCreated $event): void
    {
        $this->id       = $event->cardId;
        $this->boardId  = $event->boardId;
        $this->columnId = $event->columnId;
        $this->title    = $event->title;
    }

    private function applyCardMoved(CardMoved $event): void
    {
        $this->columnId = $event->toColumnId;
    }
}