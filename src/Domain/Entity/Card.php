<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Event\Card\CardArchived;
use App\Domain\Event\Card\CardAssigned;
use App\Domain\Event\Card\CardCommented;
use App\Domain\Event\Card\CardCreated;
use App\Domain\Event\Card\CardMoved;
use App\Domain\Event\DomainEvent;
use App\Domain\Exception\Card\CardAlreadyArchived;
use App\Domain\ValueObject\ColumnId;
use App\Domain\ValueObject\Id;

final class Card extends AggregateRoot
{
    private Id $boardId;
    private ColumnId $columnId;
    private string $title;
    private ?Id $assigneeId = null;
    private bool $archived  = false;

    public static function create(Id $cardId, Id $boardId, ColumnId $columnId, string $title): self
    {
        $card = new self();
        $card->recordThat(new CardCreated($cardId, $boardId, $columnId, $title));

        return $card;
    }

    public function moveTo(ColumnId $columnId): void
    {
        $this->guardNotArchived();

        if ($columnId->equals($this->columnId)) {
            return;
        }

        $this->recordThat(new CardMoved($this->id, $this->columnId, $columnId));
    }

    public function assignTo(Id $assigneeId): void
    {
        $this->guardNotArchived();

        if ($this->assigneeId !== null && $this->assigneeId->equals($assigneeId)) {
            return;
        }

        $this->recordThat(new CardAssigned($this->id, $assigneeId));
    }

    public function comment(Id $commentId, Id $authorId, string $body): void
    {
        $this->guardNotArchived();

        $this->recordThat(new CardCommented($this->id, $commentId, $authorId, $body));
    }

    public function archive(): void
    {
        if ($this->archived) {
            return;
        }

        $this->recordThat(new CardArchived($this->id));
    }

    public function columnId(): ColumnId
    {
        return $this->columnId;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function assigneeId(): ?Id
    {
        return $this->assigneeId;
    }

    public function isArchived(): bool
    {
        return $this->archived;
    }

    protected function apply(DomainEvent $event): void
    {
        match (true) {
            $event instanceof CardCreated   => $this->applyCardCreated($event),
            $event instanceof CardMoved     => $this->applyCardMoved($event),
            $event instanceof CardAssigned  => $this->applyCardAssigned($event),
            $event instanceof CardCommented => null,
            $event instanceof CardArchived  => $this->applyCardArchived($event),
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

    private function applyCardAssigned(CardAssigned $event): void
    {
        $this->assigneeId = $event->assigneeId;
    }

    private function applyCardArchived(CardArchived $event): void
    {
        $this->archived = true;
    }

    private function guardNotArchived(): void
    {
        if ($this->archived) {
            throw new CardAlreadyArchived($this->id);
        }
    }
}
