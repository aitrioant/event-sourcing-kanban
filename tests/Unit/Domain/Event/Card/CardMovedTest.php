<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Event\Card;

use App\Domain\Event\Card\CardMoved;
use App\Domain\Event\EventType;
use App\Domain\ValueObject\ColumnId;
use App\Domain\ValueObject\Id;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CardMovedTest extends TestCase
{
    #[Test]
    public function payload_round_trips_preserving_from_and_to_columns(): void
    {
        $cardId = Id::generate();
        $from   = ColumnId::doing();
        $to     = ColumnId::review();

        $event = new CardMoved($cardId, $from, $to);

        $rebuilt = CardMoved::fromPayload($cardId, $event->toPayload());

        self::assertTrue($rebuilt->cardId->equals($cardId));
        self::assertTrue($rebuilt->fromColumnId->equals($from));
        self::assertTrue($rebuilt->toColumnId->equals($to));
    }

    #[Test]
    public function declares_card_moved_event_type(): void
    {
        self::assertSame(EventType::CardMoved, CardMoved::eventType());
    }
}