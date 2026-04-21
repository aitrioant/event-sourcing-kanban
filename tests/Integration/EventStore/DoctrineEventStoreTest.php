<?php

declare(strict_types=1);

namespace App\Tests\Integration\EventStore;

use App\Domain\Event\Card\CardCreated;
use App\Domain\Event\Card\CardMoved;
use App\Domain\ValueObject\ColumnId;
use App\Domain\ValueObject\Id;
use App\Infrastructure\EventStore\ConcurrencyException;
use App\Infrastructure\EventStore\DoctrineEventStore;
use App\Infrastructure\EventStore\EventMap;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DoctrineEventStoreTest extends KernelTestCase
{
    private Connection $connection;
    private DoctrineEventStore $store;

    protected function setUp(): void
    {
        self::bootKernel();
        $container        = self::getContainer();
        $this->connection = $container->get(Connection::class);
        $this->store      = new DoctrineEventStore($this->connection, new EventMap());

        $this->connection->executeStatement('TRUNCATE TABLE events RESTART IDENTITY');
    }

    #[Test]
    public function append_then_load_returns_events_in_version_order(): void
    {
        $cardId  = Id::generate();
        $boardId = Id::generate();
        $todo    = ColumnId::todo();
        $doing   = ColumnId::doing();
        $done    = ColumnId::done();

        $this->store->append($cardId, 0, [
            new CardCreated($cardId, $boardId, $todo, 'Ship it'),
            new CardMoved($cardId, $todo, $doing),
            new CardMoved($cardId, $doing, $done),
        ]);

        $loaded = $this->store->load($cardId);

        self::assertCount(3, $loaded);
        self::assertInstanceOf(CardCreated::class, $loaded[0]);
        self::assertInstanceOf(CardMoved::class, $loaded[1]);
        self::assertInstanceOf(CardMoved::class, $loaded[2]);
        self::assertTrue($loaded[1]->toColumnId->equals($doing));
        self::assertTrue($loaded[2]->toColumnId->equals($done));
    }

    #[Test]
    public function load_isolates_streams_by_id(): void
    {
        $a = Id::generate();
        $b = Id::generate();
        $board = Id::generate();
        $todo  = ColumnId::todo();

        $this->store->append($a, 0, [new CardCreated($a, $board, $todo, 'A')]);
        $this->store->append($b, 0, [new CardCreated($b, $board, $todo, 'B')]);

        self::assertCount(1, $this->store->load($a));
        self::assertCount(1, $this->store->load($b));
        self::assertSame('A', $this->store->load($a)[0]->title);
        self::assertSame('B', $this->store->load($b)[0]->title);
    }

    #[Test]
    public function load_returns_empty_array_for_unknown_stream(): void
    {
        self::assertSame([], $this->store->load(Id::generate()));
    }

    #[Test]
    public function concurrent_write_at_same_version_throws_concurrency_exception(): void
    {
        $cardId = Id::generate();
        $board  = Id::generate();
        $todo   = ColumnId::todo();
        $doing  = ColumnId::doing();
        $blocked = ColumnId::blocked();

        $this->store->append($cardId, 0, [new CardCreated($cardId, $board, $todo, 'Race')]);
        // Writer A wins version 2.
        $this->store->append($cardId, 1, [new CardMoved($cardId, $todo, $doing)]);

        // Writer B loaded the stream before A wrote, also targets version 2.
        $this->expectException(ConcurrencyException::class);
        $this->store->append($cardId, 1, [new CardMoved($cardId, $todo, $blocked)]);
    }

    #[Test]
    public function concurrency_failure_persists_no_partial_batch(): void
    {
        $cardId = Id::generate();
        $board  = Id::generate();
        $todo   = ColumnId::todo();
        $doing  = ColumnId::doing();
        $done   = ColumnId::done();

        $this->store->append($cardId, 0, [new CardCreated($cardId, $board, $todo, 'Atomic')]);
        $this->store->append($cardId, 1, [new CardMoved($cardId, $todo, $doing)]);

        // Batch of two events where the FIRST would collide at version 2. The
        // transactional append must roll back — no event in this batch should land.
        try {
            $this->store->append($cardId, 1, [
                new CardMoved($cardId, $todo, $done),
                new CardMoved($cardId, $done, $doing),
            ]);
            self::fail('Expected ConcurrencyException');
        } catch (ConcurrencyException) {
            // expected
        }

        self::assertCount(2, $this->store->load($cardId), 'Failed batch must not persist any event');
    }
}