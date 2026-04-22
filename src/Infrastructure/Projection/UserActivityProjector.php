<?php

declare(strict_types=1);

namespace App\Infrastructure\Projection;

use App\Application\Projection\Projector;
use App\Domain\Event\Card\CardAssigned;
use App\Domain\Event\Card\CardCommented;
use App\Domain\Event\DomainEvent;
use App\Domain\ValueObject\Id;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;

/**
 * Counts events per user per day. Currently only the events that carry an actor
 * contribute (assignee on CardAssigned, author on CardCommented). Other events
 * have no actor in their payload yet.
 */
final readonly class UserActivityProjector implements Projector
{
    public function __construct(private Connection $connection)
    {
    }

    public function name(): string
    {
        return 'user_activity';
    }

    public function project(DomainEvent $event, DateTimeImmutable $occurredAt): void
    {
        $userId = $this->actor($event);
        if ($userId === null) {
            return;
        }

        $this->connection->executeStatement(
            <<<'SQL'
                INSERT INTO user_activity (user_id, day, events) VALUES (:user_id, :day, 1)
                ON CONFLICT (user_id, day) DO UPDATE
                  SET events = user_activity.events + 1
            SQL,
            [
                'user_id' => $userId->toString(),
                'day'     => $occurredAt->format('Y-m-d'),
            ],
        );
    }

    public function reset(): void
    {
        $this->connection->executeStatement('TRUNCATE TABLE user_activity');
    }

    private function actor(DomainEvent $event): ?Id
    {
        return match (true) {
            $event instanceof CardAssigned  => $event->assigneeId,
            $event instanceof CardCommented => $event->authorId,
            default                         => null,
        };
    }
}
