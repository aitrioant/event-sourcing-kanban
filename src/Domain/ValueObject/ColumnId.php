<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;

final readonly class ColumnId
{
    public const BACKLOG = 'backlog';
    public const TODO    = 'todo';
    public const DOING   = 'doing';
    public const BLOCKED = 'blocked';
    public const REVIEW  = 'review';
    public const DONE    = 'done';

    private const KNOWN = [
        self::BACKLOG,
        self::TODO,
        self::DOING,
        self::BLOCKED,
        self::REVIEW,
        self::DONE,
    ];

    private function __construct(private string $value)
    {
    }

    public static function fromString(string $value): self
    {
        if (!in_array($value, self::KNOWN, true)) {
            throw new InvalidArgumentException(sprintf(
                'Unknown column "%s". Known: %s.',
                $value,
                implode(', ', self::KNOWN),
            ));
        }

        return new self($value);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}