<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Event\Card;

use App\Domain\Event\Card\CardCommented;
use App\Domain\Event\EventType;
use App\Domain\ValueObject\Id;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CardCommentedTest extends TestCase
{
    #[Test]
    public function payload_round_trips_preserving_all_fields(): void
    {
        $cardId    = Id::generate();
        $commentId = Id::generate();
        $authorId  = Id::generate();

        $event   = new CardCommented($cardId, $commentId, $authorId, 'Looks good to me');
        $rebuilt = CardCommented::fromPayload($cardId, $event->toPayload());

        self::assertTrue($rebuilt->cardId->equals($cardId));
        self::assertTrue($rebuilt->commentId->equals($commentId));
        self::assertTrue($rebuilt->authorId->equals($authorId));
        self::assertSame('Looks good to me', $rebuilt->body);
    }

    #[Test]
    public function declares_card_commented_event_type(): void
    {
        self::assertSame(EventType::CardCommented, CardCommented::eventType());
    }
}