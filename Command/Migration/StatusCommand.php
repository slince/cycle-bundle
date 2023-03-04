<?php

declare(strict_types=1);

/*
 * This file is part of the slince/cycle-bundle package.
 *
 * (c) Slince <taosikai@yeah.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Slince\CycleBundle\Command\Migration;

use Cycle\Migrations\State;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Show all available migrations and their statuses
 */
#[AsCommand(name: 'cycle:migration:status', description: 'Get list of all available migrations and their statuses')]
final class StatusCommand extends AbstractCommand
{
    protected const PENDING = '<fg=red>not executed yet</fg=red>';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->migrator->configure();

        $io = new SymfonyStyle($input, $output);

        if (!$this->verifyEnvironment($input, $io)) {
            $io->writeln('<comment>No migrations were found.</comment>');

            return self::SUCCESS;
        }

        $table = $io->createTable();
        $table->setHeaders(['Migration', 'Created at', 'Executed at']);

        foreach ($this->migrator->getMigrations() as $migration) {
            $state = $migration->getState();

            $table->addRow(
                [
                    $state->getName(),
                    $state->getTimeCreated()->format('Y-m-d H:i:s'),
                    $state->getStatus() == State::STATUS_PENDING
                        ? self::PENDING
                        : '<info>'.$state->getTimeExecuted()->format('Y-m-d H:i:s').'</info>',
                ]
            );
        }

        $table->render();

        return self::SUCCESS;
    }
}
