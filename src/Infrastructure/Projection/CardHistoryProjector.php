<?php

declare(strict_types=1);

namespace App\Infrastructure\Projection;

use App\Application\Projection\Projector;
use App\Domain\Event\Card\CardArchived;
use App\Domain\Event\Card\CardAssigned;
use App\Domain\Event\Card\CardCommented;
use App\Domain\Event\Card\CardCreated;
use App\Domain\Event\Card\CardMoved;
use App\Domain\Event\DomainEvent;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;

final readonly class CardHistoryProjector implements Projector
{
    public function __construct(private Connection $connection)
    {
    }

    public function name(): string
    {
        return 'card_history';
    }

    public function project(DomainEvent $event, DateTimeImmutable $occurredAt): void
    {
        $description = $this->describe($event);
        if ($description === null) {
            return;
        }

        $this->connection->insert('card_history', [
            'card_id'     => $event->aggregateId()->toString(),
            'event_type'  => $event::eventType()->value,
            'description' => $description,
            'occurred_at' => $occurredAt->format('Y-m-d H:i:s.uP'),
        ]);
    }

    public function reset(): void
    {
        $this->connection->executeStatement('TRUNCATE TABLE card_history RESTART IDENTITY');
    }

    private function describe(DomainEvent $event): ?string
    {
        return match (true) {
            $event instanceof CardCreated   => sprintf('Card created in %s: "%s"', $event->columnId, $event->title),
            $event instanceof CardMoved     => sprintf('Moved from %s to %s', $event->fromColumnId, $event->toColumnId),
            $event instanceof CardAssigned  => sprintf('Assigned to %s', $event->assigneeId),
            $event instanceof CardCommented => sprintf('%s commented: %s', $event->authorId, $event->body),
            $event instanceof CardArchived  => 'Archived',
            default                         => null,
        };
    }
}