<?php

declare(strict_types=1);

namespace App\Command;

use App\Application\Projection\ProjectionRunner;
use App\Application\Projection\ProjectorRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:projection:rebuild',
    description: 'Truncate a projection (or all of them) and replay it from the event log.',
)]
final class RebuildProjectionCommand extends Command
{
    public function __construct(
        private readonly ProjectionRunner $runner,
        private readonly ProjectorRegistry $registry,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::OPTIONAL, 'Projector name to rebuild')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Rebuild every projector');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io   = new SymfonyStyle($input, $output);
        $name = $input->getArgument('name');
        $all  = (bool) $input->getOption('all');

        if (!$all && $name === null) {
            $io->error(sprintf(
                'Pass a projector name (one of: %s) or --all.',
                implode(', ', $this->registry->names()),
            ));

            return Command::FAILURE;
        }

        if ($all) {
            $count = $this->runner->rebuildAll();
            $io->success(sprintf('Replayed %d events into all projectors.', $count));

            return Command::SUCCESS;
        }

        $count = $this->runner->rebuild($name);
        $io->success(sprintf('Replayed %d events into "%s".', $count, $name));

        return Command::SUCCESS;
    }
}