<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Event\Card;

use App\Domain\Event\Card\CardArchived;
use App\Domain\Event\EventType;
use App\Domain\ValueObject\Id;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CardArchivedTest extends TestCase
{
    #[Test]
    public function payload_round_trips_preserving_card_id(): void
    {
        $cardId = Id::generate();

        $event   = new CardArchived($cardId);
        $rebuilt = CardArchived::fromPayload($cardId, $event->toPayload());

        self::assertTrue($rebuilt->cardId->equals($cardId));
        self::assertSame([], $event->toPayload());
    }

    #[Test]
    public function declares_card_archived_event_type(): void
    {
        self::assertSame(EventType::CardArchived, CardArchived::eventType());
    }
}