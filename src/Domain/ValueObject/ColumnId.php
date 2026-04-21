<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;

final readonly class ColumnId
{
    public const string BACKLOG = 'backlog';
    public const string TODO    = 'todo';
    public const string DOING   = 'doing';
    public const string BLOCKED = 'blocked';
    public const string REVIEW  = 'review';
    public const string DONE    = 'done';

    private function __construct(private string $value)
    {
    }

    public static function backlog(): self
    {
        return new self(self::BACKLOG);
    }

    public static function todo(): self
    {
        return new self(self::TODO);
    }

    public static function doing(): self
    {
        return new self(self::DOING);
    }

    public static function blocked(): self
    {
        return new self(self::BLOCKED);
    }

    public static function review(): self
    {
        return new self(self::REVIEW);
    }

    public static function done(): self
    {
        return new self(self::DONE);
    }

    public static function fromString(string $value): self
    {
        return match ($value) {
            self::BACKLOG => self::backlog(),
            self::TODO    => self::todo(),
            self::DOING   => self::doing(),
            self::BLOCKED => self::blocked(),
            self::REVIEW  => self::review(),
            self::DONE    => self::done(),
            default       => throw new InvalidArgumentException("Unknown column: $value"),
        };
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