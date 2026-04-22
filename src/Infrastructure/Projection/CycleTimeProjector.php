<?php

declare(strict_types=1);

namespace App\Infrastructure\Projection;

use App\Application\Projection\Projector;
use App\Domain\Event\Card\CardMoved;
use App\Domain\Event\DomainEvent;
use App\Domain\ValueObject\ColumnId;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;

/**
 * in_progress_at: first time the card entered "doing" (kept on subsequent re-entries via COALESCE).
 * done_at:        most recent time the card landed in "done".
 */
final readonly class CycleTimeProjector implements Projector
{
    public function __construct(private Connection $connection)
    {
    }

    public function name(): string
    {
        return 'cycle_time';
    }

    public function project(DomainEvent $event, DateTimeImmutable $occurredAt): void
    {
        if (!$event instanceof CardMoved) {
            return;
        }

        $cardId = $event->cardId->toString();
        $when   = $occurredAt->format('Y-m-d H:i:s.uP');
        $target = $event->toColumnId->toString();

        if ($target === ColumnId::DOING) {
            $this->connection->executeStatement(
                <<<'SQL'
                    INSERT INTO cycle_time (card_id, in_progress_at) VALUES (:card_id, :when)
                    ON CONFLICT (card_id) DO UPDATE
                      SET in_progress_at = COALESCE(cycle_time.in_progress_at, EXCLUDED.in_progress_at)
                SQL,
                ['card_id' => $cardId, 'when' => $when],
            );

            return;
        }

        if ($target === ColumnId::DONE) {
            $this->connection->executeStatement(
                <<<'SQL'
                    INSERT INTO cycle_time (card_id, done_at) VALUES (:card_id, :when)
                    ON CONFLICT (card_id) DO UPDATE
                      SET done_at = EXCLUDED.done_at
                SQL,
                ['card_id' => $cardId, 'when' => $when],
            );
        }
    }

    public function reset(): void
    {
        $this->connection->executeStatement('TRUNCATE TABLE cycle_time');
    }
}