<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity;

use App\Domain\Entity\Card;
use App\Domain\Event\Card\CardArchived;
use App\Domain\Event\Card\CardAssigned;
use App\Domain\Event\Card\CardCommented;
use App\Domain\Event\Card\CardCreated;
use App\Domain\Event\Card\CardMoved;
use App\Domain\Exception\Card\CardAlreadyArchived;
use App\Domain\ValueObject\ColumnId;
use App\Domain\ValueObject\Id;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CardTest extends TestCase
{
    private Id $cardId;
    private Id $boardId;
    private ColumnId $todo;
    private ColumnId $doing;
    private ColumnId $done;

    protected function setUp(): void
    {
        $this->cardId  = Id::generate();
        $this->boardId = Id::generate();
        $this->todo    = ColumnId::todo();
        $this->doing   = ColumnId::doing();
        $this->done    = ColumnId::done();
    }

    #[Test]
    public function create_records_a_card_created_event_and_sets_version_to_one(): void
    {
        $card = Card::create($this->cardId, $this->boardId, $this->todo, 'Ship it');

        $events = $card->pullUncommittedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(CardCreated::class, $events[0]);
        self::assertTrue($events[0]->cardId->equals($this->cardId));
        self::assertTrue($events[0]->columnId->equals($this->todo));
        self::assertSame('Ship it', $events[0]->title);
        self::assertSame(1, $card->version());
    }

    #[Test]
    public function pull_uncommitted_events_is_idempotent_after_draining(): void
    {
        $card = Card::create($this->cardId, $this->boardId, $this->todo, 'Ship it');

        $card->pullUncommittedEvents();

        self::assertSame([], $card->pullUncommittedEvents());
    }

    #[Test]
    public function move_to_new_column_records_card_moved_event(): void
    {
        $card = Card::create($this->cardId, $this->boardId, $this->todo, 'Ship it');
        $card->pullUncommittedEvents();

        $card->moveTo($this->doing);

        $events = $card->pullUncommittedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(CardMoved::class, $events[0]);
        self::assertTrue($events[0]->fromColumnId->equals($this->todo));
        self::assertTrue($events[0]->toColumnId->equals($this->doing));
        self::assertTrue($card->columnId()->equals($this->doing));
        self::assertSame(2, $card->version());
    }

    #[Test]
    public function move_to_current_column_is_a_no_op(): void
    {
        $card = Card::create($this->cardId, $this->boardId, $this->todo, 'Ship it');
        $card->pullUncommittedEvents();

        $card->moveTo($this->todo);

        self::assertSame([], $card->pullUncommittedEvents());
        self::assertSame(1, $card->version());
    }

    #[Test]
    public function replay_reconstructs_final_state_without_re_emitting_events(): void
    {
        $history = [
            new CardCreated($this->cardId, $this->boardId, $this->todo, 'Ship it'),
            new CardMoved($this->cardId, $this->todo, $this->doing),
            new CardMoved($this->cardId, $this->doing, $this->done),
        ];

        $card = Card::replay($history);

        self::assertTrue($card->id()->equals($this->cardId));
        self::assertTrue($card->columnId()->equals($this->done));
        self::assertSame('Ship it', $card->title());
        self::assertSame(3, $card->version());
        self::assertSame([], $card->pullUncommittedEvents(), 'Replay must not produce uncommitted events');
    }

    #[Test]
    public function assign_to_records_card_assigned_event(): void
    {
        $card = Card::create($this->cardId, $this->boardId, $this->todo, 'Ship it');
        $card->pullUncommittedEvents();
        $assignee = Id::generate();

        $card->assignTo($assignee);

        $events = $card->pullUncommittedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(CardAssigned::class, $events[0]);
        self::assertTrue($events[0]->assigneeId->equals($assignee));
        self::assertTrue($card->assigneeId()->equals($assignee));
    }

    #[Test]
    public function assign_to_same_user_is_a_no_op(): void
    {
        $card = Card::create($this->cardId, $this->boardId, $this->todo, 'Ship it');
        $assignee = Id::generate();
        $card->assignTo($assignee);
        $card->pullUncommittedEvents();

        $card->assignTo($assignee);

        self::assertSame([], $card->pullUncommittedEvents());
    }

    #[Test]
    public function comment_records_card_commented_event(): void
    {
        $card = Card::create($this->cardId, $this->boardId, $this->todo, 'Ship it');
        $card->pullUncommittedEvents();
        $commentId = Id::generate();
        $authorId  = Id::generate();

        $card->comment($commentId, $authorId, 'Looks good');

        $events = $card->pullUncommittedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(CardCommented::class, $events[0]);
        self::assertTrue($events[0]->commentId->equals($commentId));
        self::assertTrue($events[0]->authorId->equals($authorId));
        self::assertSame('Looks good', $events[0]->body);
    }

    #[Test]
    public function archive_records_card_archived_event_and_marks_card_archived(): void
    {
        $card = Card::create($this->cardId, $this->boardId, $this->todo, 'Ship it');
        $card->pullUncommittedEvents();

        $card->archive();

        $events = $card->pullUncommittedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(CardArchived::class, $events[0]);
        self::assertTrue($card->isArchived());
    }

    #[Test]
    public function archive_twice_is_a_no_op(): void
    {
        $card = Card::create($this->cardId, $this->boardId, $this->todo, 'Ship it');
        $card->archive();
        $card->pullUncommittedEvents();

        $card->archive();

        self::assertSame([], $card->pullUncommittedEvents());
    }

    #[Test]
    public function moving_an_archived_card_is_rejected(): void
    {
        $card = Card::create($this->cardId, $this->boardId, $this->todo, 'Ship it');
        $card->archive();

        $this->expectException(CardAlreadyArchived::class);
        $card->moveTo($this->doing);
    }

    #[Test]
    public function commenting_on_an_archived_card_is_rejected(): void
    {
        $card = Card::create($this->cardId, $this->boardId, $this->todo, 'Ship it');
        $card->archive();

        $this->expectException(CardAlreadyArchived::class);
        $card->comment(Id::generate(), Id::generate(), 'too late');
    }

    #[Test]
    public function assigning_an_archived_card_is_rejected(): void
    {
        $card = Card::create($this->cardId, $this->boardId, $this->todo, 'Ship it');
        $card->archive();

        $this->expectException(CardAlreadyArchived::class);
        $card->assignTo(Id::generate());
    }
}