<?php

declare(strict_types=1);

namespace App\Tests\Integration\Projection;

use App\Application\Projection\ProjectionRunner;
use App\Domain\Entity\Card;
use App\Domain\Repository\CardRepository;
use App\Domain\ValueObject\ColumnId;
use App\Domain\ValueObject\Id;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ProjectionTest extends KernelTestCase
{
    private Connection $connection;
    private CardRepository $cards;
    private ProjectionRunner $runner;

    protected function setUp(): void
    {
        self::bootKernel();
        $container        = self::getContainer();
        $this->connection = $container->get(Connection::class);
        $this->cards      = $container->get(CardRepository::class);
        $this->runner     = $container->get(ProjectionRunner::class);

        $this->connection->executeStatement('TRUNCATE TABLE events RESTART IDENTITY');
        $this->connection->executeStatement('TRUNCATE TABLE board_view');
        $this->connection->executeStatement('TRUNCATE TABLE card_history RESTART IDENTITY');
        $this->connection->executeStatement('TRUNCATE TABLE user_activity');
        $this->connection->executeStatement('TRUNCATE TABLE cycle_time');
    }

    #[Test]
    public function appending_events_synchronously_updates_board_view(): void
    {
        $cardId  = Id::generate();
        $boardId = Id::generate();
        $alice   = Id::generate();

        $card = Card::create($cardId, $boardId, ColumnId::todo(), 'Ship it');
        $card->moveTo(ColumnId::doing());
        $card->assignTo($alice);
        $this->cards->save($card);

        $row = $this->connection->fetchAssociative(
            'SELECT * FROM board_view WHERE card_id = :id',
            ['id' => $cardId->toString()],
        );

        self::assertNotFalse($row);
        self::assertSame(ColumnId::DOING, $row['column_id']);
        self::assertSame($alice->toString(), $row['assignee_id']);
        self::assertFalse((bool) $row['archived']);
        self::assertSame('Ship it', $row['title']);
    }

    #[Test]
    public function archive_marks_board_view_row_archived(): void
    {
        $cardId  = Id::generate();
        $boardId = Id::generate();

        $card = Card::create($cardId, $boardId, ColumnId::todo(), 'Stale');
        $card->archive();
        $this->cards->save($card);

        $archived = $this->connection->fetchOne(
            'SELECT archived FROM board_view WHERE card_id = :id',
            ['id' => $cardId->toString()],
        );

        self::assertTrue((bool) $archived);
    }

    #[Test]
    public function card_history_records_one_row_per_event_in_order(): void
    {
        $cardId  = Id::generate();
        $boardId = Id::generate();

        $card = Card::create($cardId, $boardId, ColumnId::todo(), 'Ship it');
        $card->moveTo(ColumnId::doing());
        $card->moveTo(ColumnId::done());
        $this->cards->save($card);

        $rows = $this->connection->fetchAllAssociative(
            'SELECT event_type FROM card_history WHERE card_id = :id ORDER BY sequence ASC',
            ['id' => $cardId->toString()],
        );

        self::assertSame(['card.created', 'card.moved', 'card.moved'], array_column($rows, 'event_type'));
    }

    #[Test]
    public function user_activity_counts_assignments_and_comments_per_user(): void
    {
        $cardId  = Id::generate();
        $boardId = Id::generate();
        $alice   = Id::generate();
        $bob     = Id::generate();

        $card = Card::create($cardId, $boardId, ColumnId::todo(), 'Ship it');
        $card->assignTo($alice);
        $card->comment(Id::generate(), $alice, 'starting');
        $card->comment(Id::generate(), $bob, 'reviewing');
        $this->cards->save($card);

        $aliceCount = $this->connection->fetchOne(
            'SELECT events FROM user_activity WHERE user_id = :id',
            ['id' => $alice->toString()],
        );
        $bobCount = $this->connection->fetchOne(
            'SELECT events FROM user_activity WHERE user_id = :id',
            ['id' => $bob->toString()],
        );

        self::assertSame(2, (int) $aliceCount);
        self::assertSame(1, (int) $bobCount);
    }

    #[Test]
    public function cycle_time_captures_first_doing_and_last_done(): void
    {
        $cardId  = Id::generate();
        $boardId = Id::generate();

        $card = Card::create($cardId, $boardId, ColumnId::todo(), 'Ship it');
        $card->moveTo(ColumnId::doing());
        $card->moveTo(ColumnId::done());
        $this->cards->save($card);

        $row = $this->connection->fetchAssociative(
            'SELECT in_progress_at, done_at FROM cycle_time WHERE card_id = :id',
            ['id' => $cardId->toString()],
        );

        self::assertNotFalse($row);
        self::assertNotNull($row['in_progress_at']);
        self::assertNotNull($row['done_at']);
    }

    #[Test]
    public function cycle_time_keeps_first_doing_when_card_re_enters_doing(): void
    {
        $cardId  = Id::generate();
        $boardId = Id::generate();

        $card = Card::create($cardId, $boardId, ColumnId::todo(), 'Ship it');
        $card->moveTo(ColumnId::doing());
        $this->cards->save($card);

        $firstDoing = $this->connection->fetchOne(
            'SELECT in_progress_at FROM cycle_time WHERE card_id = :id',
            ['id' => $cardId->toString()],
        );

        // bounce out and back into doing
        $reloaded = $this->cards->get($cardId);
        $reloaded->moveTo(ColumnId::blocked());
        $reloaded->moveTo(ColumnId::doing());
        $this->cards->save($reloaded);

        $secondDoing = $this->connection->fetchOne(
            'SELECT in_progress_at FROM cycle_time WHERE card_id = :id',
            ['id' => $cardId->toString()],
        );

        self::assertSame($firstDoing, $secondDoing, 'in_progress_at must not be overwritten on re-entry');
    }

    #[Test]
    public function rebuild_truncates_and_replays_a_single_projection(): void
    {
        $cardId  = Id::generate();
        $boardId = Id::generate();
        $alice   = Id::generate();

        $card = Card::create($cardId, $boardId, ColumnId::todo(), 'Ship it');
        $card->moveTo(ColumnId::doing());
        $card->assignTo($alice);
        $this->cards->save($card);

        // Corrupt the projection: wipe the board view directly.
        $this->connection->executeStatement('TRUNCATE TABLE board_view');
        self::assertSame(0, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM board_view'));

        $count = $this->runner->rebuild('board_view');

        self::assertSame(3, $count, 'should have replayed CardCreated + CardMoved + CardAssigned');

        $row = $this->connection->fetchAssociative(
            'SELECT * FROM board_view WHERE card_id = :id',
            ['id' => $cardId->toString()],
        );
        self::assertNotFalse($row);
        self::assertSame(ColumnId::DOING, $row['column_id']);
        self::assertSame($alice->toString(), $row['assignee_id']);
    }

    #[Test]
    public function rebuild_all_replays_every_projection(): void
    {
        $cardId  = Id::generate();
        $boardId = Id::generate();
        $alice   = Id::generate();

        $card = Card::create($cardId, $boardId, ColumnId::todo(), 'Ship it');
        $card->moveTo(ColumnId::doing());
        $card->assignTo($alice);
        $card->comment(Id::generate(), $alice, 'go');
        $card->moveTo(ColumnId::done());
        $this->cards->save($card);

        // Wipe everything but the event log.
        $this->connection->executeStatement('TRUNCATE TABLE board_view');
        $this->connection->executeStatement('TRUNCATE TABLE card_history RESTART IDENTITY');
        $this->connection->executeStatement('TRUNCATE TABLE user_activity');
        $this->connection->executeStatement('TRUNCATE TABLE cycle_time');

        $this->runner->rebuildAll();

        self::assertSame(1, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM board_view'));
        self::assertSame(5, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM card_history'));
        self::assertSame(2, (int) $this->connection->fetchOne(
            'SELECT events FROM user_activity WHERE user_id = :id',
            ['id' => $alice->toString()],
        ));
        $cycle = $this->connection->fetchAssociative('SELECT in_progress_at, done_at FROM cycle_time');
        self::assertNotFalse($cycle);
        self::assertNotNull($cycle['in_progress_at']);
        self::assertNotNull($cycle['done_at']);
    }
}