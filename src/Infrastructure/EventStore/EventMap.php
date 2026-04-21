<?php

declare(strict_types=1);

namespace App\Infrastructure\EventStore;

use App\Domain\Event\Card\CardCreated;
use App\Domain\Event\Card\CardMoved;
use App\Domain\Event\DomainEvent;
use App\Domain\Event\EventType;
use InvalidArgumentException;

final class EventMap
{
    /** @var array<string, class-string<DomainEvent>> */
    private array $map;

    public function __construct()
    {
        $this->map = [
            EventType::CardCreated->value => CardCreated::class,
            EventType::CardMoved->value   => CardMoved::class,
        ];
    }

    /** @return class-string<DomainEvent> */
    public function classFor(EventType $type): string
    {
        return $this->map[$type->value]
            ?? throw new InvalidArgumentException("Unmapped event type: {$type->value}");
    }
}