<?php

declare(strict_types=1);

namespace App\Infrastructure\Projection;

use App\Application\Projection\Projector;
use App\Domain\Event\Card\CardArchived;
use App\Domain\Event\Card\CardAssigned;
use App\Domain\Event\Card\CardCreated;
use App\Domain\Event\Card\CardMoved;
use App\Domain\Event\DomainEvent;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

final readonly class BoardViewProjector implements Projector
{
    public function __construct(private Connection $connection)
    {
    }

    public function name(): string
    {
        return 'board_view';
    }

    public function project(DomainEvent $event, DateTimeImmutable $occurredAt): void
    {
        match (true) {
            $event instanceof CardCreated  => $this->onCreated($event),
            $event instanceof CardMoved    => $this->onMoved($event),
            $event instanceof CardAssigned => $this->onAssigned($event),
            $event instanceof CardArchived => $this->onArchived($event),
            default                        => null,
        };
    }

    public function reset(): void
    {
        $this->connection->executeStatement('TRUNCATE TABLE board_view');
    }

    private function onCreated(CardCreated $event): void
    {
        // archived defaults to FALSE in the schema — let the DB set it.
        $this->connection->insert('board_view', [
            'card_id'   => $event->cardId->toString(),
            'board_id'  => $event->boardId->toString(),
            'column_id' => $event->columnId->toString(),
            'title'     => $event->title,
        ]);
    }

    private function onMoved(CardMoved $event): void
    {
        $this->connection->update(
            'board_view',
            ['column_id' => $event->toColumnId->toString()],
            ['card_id'   => $event->cardId->toString()],
        );
    }

    private function onAssigned(CardAssigned $event): void
    {
        $this->connection->update(
            'board_view',
            ['assignee_id' => $event->assigneeId->toString()],
            ['card_id'     => $event->cardId->toString()],
        );
    }

    private function onArchived(CardArchived $event): void
    {
        $this->connection->update(
            'board_view',
            ['archived' => true],
            ['card_id'  => $event->cardId->toString()],
            ['archived' => ParameterType::BOOLEAN],
        );
    }
}