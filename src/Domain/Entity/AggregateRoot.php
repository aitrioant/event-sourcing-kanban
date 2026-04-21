<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Event\DomainEvent;
use App\Domain\ValueObject\Id;

abstract class AggregateRoot
{
    protected Id $id;
    protected int $version = 0;
    /** @var DomainEvent[] */
    private array $uncommittedEvents = [];

    final public function id(): Id
    {
        return $this->id;
    }

    final public function version(): int
    {
        return $this->version;
    }

    /** @return DomainEvent[] */
    final public function pullUncommittedEvents(): array
    {
        $events = $this->uncommittedEvents;
        $this->uncommittedEvents = [];

        return $events;
    }

    final protected function recordThat(DomainEvent $event): void
    {
        $this->uncommittedEvents[] = $event;
        $this->apply($event);
        $this->version++;
    }

    /**
     * Rehydrate an aggregate from its persisted event stream.
     *
     * @param DomainEvent[] $events
     */
    final public static function replay(iterable $events): static
    {
        $instance = new static();
        foreach ($events as $event) {
            $instance->apply($event);
            $instance->version++;
        }

        return $instance;
    }

    abstract protected function apply(DomainEvent $event): void;
}