<?php

declare(strict_types=1);

namespace App\Domain\Exception\Card;

use App\Domain\ValueObject\Id;
use RuntimeException;

final class CardNotFound extends RuntimeException
{
    public function __construct(Id $id)
    {
        parent::__construct("Card {$id->toString()} not found.");
    }
}