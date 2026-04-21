<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Doctrine;

use App\Domain\Entity\Card;
use App\Domain\Event\Card\CardCreated;
use App\Domain\Event\Card\CardMoved;
use App\Domain\Exception\Card\CardNotFound;
use App\Domain\ValueObject\ColumnId;
use App\Domain\ValueObject\Id;
use App\Infrastructure\Doctrine\EventSourcedCardRepository;
use App\Infrastructure\EventStore\EventStore;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class EventSourcedCardRepositoryTest extends TestCase
{
    #[Test]
    public function save_new_card_appends_at_expected_version_zero(): void
    {
        $cardId  = Id::generate();
        $boardId = Id::generate();
        $card    = Card::create($cardId, $boardId, ColumnId::todo(), 'Ship it');

        $store = $this->createMock(EventStore::class);
        $store->expects(self::once())
            ->method('append')
            ->with(
                self::callback(fn (Id $id) => $id->equals($cardId)),
                0,
                self::callback(function (array $events): bool {
                    return count($events) === 1 && $events[0] instanceof CardCreated;
                }),
            );

        (new EventSourcedCardRepository($store))->save($card);
    }

    #[Test]
    public function save_after_load_appends_only_uncommitted_events_at_replayed_version(): void
    {
        $cardId  = Id::generate();
        $boardId = Id::generate();
        $todo    = ColumnId::todo();
        $doing   = ColumnId::doing();

        // Simulate a card with a persisted history of 2 events, freshly loaded.
        $card = Card::replay([
            new CardCreated($cardId, $boardId, $todo, 'Ship it'),
            new CardMoved($cardId, $todo, $doing),
        ]);

        // New change on top of the replayed state.
        $card->moveTo(ColumnId::done());

        $store = $this->createMock(EventStore::class);
        $store->expects(self::once())
            ->method('append')
            ->with(
                self::callback(fn (Id $id) => $id->equals($cardId)),
                2, // expectedVersion = current(3) - uncommitted(1)
                self::callback(function (array $events): bool {
                    return count($events) === 1 && $events[0] instanceof CardMoved;
                }),
            );

        (new EventSourcedCardRepository($store))->save($card);
    }

    #[Test]
    public function save_with_no_uncommitted_events_does_not_touch_the_store(): void
    {
        $card = Card::replay([
            new CardCreated(Id::generate(), Id::generate(), ColumnId::todo(), 'Ship it'),
        ]);

        $store = $this->createMock(EventStore::class);
        $store->expects(self::never())->method('append');

        (new EventSourcedCardRepository($store))->save($card);
    }

    #[Test]
    public function get_throws_card_not_found_when_stream_is_empty(): void
    {
        $missingId = Id::generate();

        $store = $this->createMock(EventStore::class);
        $store->method('load')->with($missingId)->willReturn([]);

        $this->expectException(CardNotFound::class);

        (new EventSourcedCardRepository($store))->get($missingId);
    }

    #[Test]
    public function get_replays_stream_into_an_aggregate(): void
    {
        $cardId  = Id::generate();
        $boardId = Id::generate();
        $todo    = ColumnId::todo();
        $doing   = ColumnId::doing();

        $store = $this->createMock(EventStore::class);
        $store->method('load')->with($cardId)->willReturn([
            new CardCreated($cardId, $boardId, $todo, 'Ship it'),
            new CardMoved($cardId, $todo, $doing),
        ]);

        $card = (new EventSourcedCardRepository($store))->get($cardId);

        self::assertTrue($card->id()->equals($cardId));
        self::assertTrue($card->columnId()->equals($doing));
        self::assertSame(2, $card->version());
    }
}