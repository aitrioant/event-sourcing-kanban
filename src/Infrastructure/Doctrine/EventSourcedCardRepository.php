<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine;

use App\Domain\Entity\Card;
use App\Domain\Exception\Card\CardNotFound;
use App\Domain\Repository\CardRepository;
use App\Domain\ValueObject\Id;
use App\Infrastructure\EventStore\EventStore;

final readonly class EventSourcedCardRepository implements CardRepository
{
    public function __construct(private EventStore $events)
    {
    }

    public function get(Id $id): Card
    {
        $history = $this->events->load($id);
        if ($history === []) {
            throw new CardNotFound($id);
        }

        return Card::replay($history);
    }

    public function save(Card $card): void
    {
        $uncommitted = $card->pullUncommittedEvents();
        if ($uncommitted === []) {
            return;
        }

        $expectedVersion = $card->version() - count($uncommitted);
        $this->events->append($card->id(), $expectedVersion, $uncommitted);
    }
}