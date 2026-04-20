<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Card;
use App\Domain\Exception\Card\CardNotFound;
use App\Domain\ValueObject\Id;

interface CardRepository
{
    /** @throws CardNotFound */
    public function get(Id $id): Card;

    public function save(Card $card): void;
}