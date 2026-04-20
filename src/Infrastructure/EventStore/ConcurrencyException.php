<?php

declare(strict_types=1);

namespace App\Infrastructure\EventStore;

use App\Domain\ValueObject\Id;
use RuntimeException;
use Throwable;

final class ConcurrencyException extends RuntimeException
{
    public function __construct(Id $streamId, int $version, ?Throwable $previous = null)
    {
        parent::__construct(
            sprintf('Concurrent write to stream %s at version %d.', $streamId->toString(), $version),
            previous: $previous,
        );
    }
}