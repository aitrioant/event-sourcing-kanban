<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Event\Card;

use App\Domain\Event\Card\CardAssigned;
use App\Domain\Event\EventType;
use App\Domain\ValueObject\Id;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CardAssignedTest extends TestCase
{
    #[Test]
    public function payload_round_trips_preserving_all_fields(): void
    {
        $cardId     = Id::generate();
        $assigneeId = Id::generate();

        $event   = new CardAssigned($cardId, $assigneeId);
        $rebuilt = CardAssigned::fromPayload($cardId, $event->toPayload());

        self::assertTrue($rebuilt->cardId->equals($cardId));
        self::assertTrue($rebuilt->assigneeId->equals($assigneeId));
    }

    #[Test]
    public function declares_card_assigned_event_type(): void
    {
        self::assertSame(EventType::CardAssigned, CardAssigned::eventType());
    }
}