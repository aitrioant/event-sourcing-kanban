<?php

declare(strict_types=1);

namespace App\Application\Projection;

use App\Domain\Event\DomainEvent;
use DateTimeImmutable;

interface Projector
{
    public function name(): string;

    public function project(DomainEvent $event, DateTimeImmutable $occurredAt): void;

    public function reset(): void;
}