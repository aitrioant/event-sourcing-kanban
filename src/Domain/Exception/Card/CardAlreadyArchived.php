<?php

declare(strict_types=1);

namespace App\Domain\Exception\Card;

use App\Domain\ValueObject\Id;
use RuntimeException;

final class CardAlreadyArchived extends RuntimeException
{
    public function __construct(Id $id)
    {
        parent::__construct("Card {$id->toString()} is archived.");
    }
}