<?php

declare(strict_types=1);

namespace App\Application\Projection;

use InvalidArgumentException;
use Traversable;

final class ProjectorRegistry
{
    /** @var array<string, Projector> */
    private array $byName = [];

    /** @param iterable<Projector> $projectors */
    public function __construct(iterable $projectors)
    {
        $list = $projectors instanceof Traversable ? iterator_to_array($projectors) : $projectors;
        foreach ($list as $projector) {
            $this->byName[$projector->name()] = $projector;
        }
    }

    /** @return Projector[] */
    public function all(): array
    {
        return array_values($this->byName);
    }

    public function get(string $name): Projector
    {
        return $this->byName[$name]
            ?? throw new InvalidArgumentException("Unknown projector: {$name}");
    }

    /** @return string[] */
    public function names(): array
    {
        return array_keys($this->byName);
    }
}