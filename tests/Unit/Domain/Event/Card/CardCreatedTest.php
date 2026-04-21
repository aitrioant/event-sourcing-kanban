<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Event\Card;

use App\Domain\Event\Card\CardCreated;
use App\Domain\Event\EventType;
use App\Domain\ValueObject\ColumnId;
use App\Domain\ValueObject\Id;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CardCreatedTest extends TestCase
{
    #[Test]
    public function payload_round_trips_preserving_all_fields(): void
    {
        $cardId   = Id::generate();
        $boardId  = Id::generate();
        $columnId = ColumnId::backlog();

        $event = new CardCreated($cardId, $boardId, $columnId, 'Plan sprint');

        $rebuilt = CardCreated::fromPayload($cardId, $event->toPayload());

        self::assertTrue($rebuilt->cardId->equals($cardId));
        self::assertTrue($rebuilt->boardId->equals($boardId));
        self::assertTrue($rebuilt->columnId->equals($columnId));
        self::assertSame('Plan sprint', $rebuilt->title);
    }

    #[Test]
    public function declares_card_created_event_type(): void
    {
        self::assertSame(EventType::CardCreated, CardCreated::eventType());
    }
}