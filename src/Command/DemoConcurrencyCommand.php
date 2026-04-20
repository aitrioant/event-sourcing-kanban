<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\Entity\Card;
use App\Domain\Event\Card\CardMoved;
use App\Domain\ValueObject\ColumnId;
use App\Domain\ValueObject\Id;
use App\Infrastructure\EventStore\ConcurrencyException;
use App\Infrastructure\EventStore\EventStore;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:demo:concurrency', description: 'Prove the optimistic concurrency guard.')]
final class DemoConcurrencyCommand extends Command
{
    public function __construct(private readonly EventStore $store)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $cardId  = Id::generate();
        $boardId = Id::generate();
        $todo    = ColumnId::fromString(ColumnId::TODO);
        $doing   = ColumnId::fromString(ColumnId::DOING);
        $blocked = ColumnId::fromString(ColumnId::BLOCKED);

        $card = Card::create($cardId, $boardId, $todo, 'Concurrency test');
        $this->store->append($cardId, 0, $card->pullUncommittedEvents());
        $io->writeln('writer A appended CardCreated at version 1');

        // Two writers loaded the stream at version 1. Both try to write version 2.
        $this->store->append($cardId, 1, [new CardMoved($cardId, $todo, $doing)]);
        $io->writeln('writer A appended CardMoved at version 2');

        try {
            $this->store->append($cardId, 1, [new CardMoved($cardId, $todo, $blocked)]);
            $io->error('writer B succeeded — guard is broken');

            return Command::FAILURE;
        } catch (ConcurrencyException $e) {
            $io->success('writer B rejected: '.$e->getMessage());

            return Command::SUCCESS;
        }
    }
}