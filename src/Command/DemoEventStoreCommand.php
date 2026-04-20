<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\Entity\Card;
use App\Domain\Repository\CardRepository;
use App\Domain\ValueObject\ColumnId;
use App\Domain\ValueObject\Id;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:demo:event-store', description: 'Round-trip a card through the event store.')]
final class DemoEventStoreCommand extends Command
{
    public function __construct(private readonly CardRepository $cards)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $cardId  = Id::generate();
        $boardId = Id::generate();

        $card = Card::create($cardId, $boardId, ColumnId::fromString(ColumnId::TODO), 'Wire up the event store');
        $card->moveTo(ColumnId::fromString(ColumnId::DOING));
        $card->moveTo(ColumnId::fromString(ColumnId::DONE));
        $this->cards->save($card);

        $io->writeln(sprintf('saved card %s at version %d', $cardId->toString(), $card->version()));

        $reloaded = $this->cards->get($cardId);
        $io->writeln(sprintf(
            'replayed card %s: column=%s version=%d',
            $reloaded->id()->toString(),
            $reloaded->columnId()->toString(),
            $reloaded->version(),
        ));

        return $reloaded->columnId()->equals(ColumnId::fromString(ColumnId::DONE)) && $reloaded->version() === 3
            ? Command::SUCCESS
            : Command::FAILURE;
    }
}
