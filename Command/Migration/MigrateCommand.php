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

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'cycle:migration:migrate', description: 'Perform one or all outstanding migrations')]
final class MigrateCommand extends AbstractCommand
{
    protected function configure()
    {
        parent::configure();

        $this->addOption('one', 'o', InputOption::VALUE_NONE, 'Execute only one (first) migration');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->verifyEnvironment($input, $io)) {
            return self::FAILURE;
        }

        $this->migrator->configure();

        $found = false;
        $count = $input->getOption('one') ? 1 : PHP_INT_MAX;

        while ($count > 0 && ($migration = $this->migrator->run())) {
            $found = true;
            $count--;

            $io->writeln(sprintf(
                "<info>Migration <comment>%s</comment> was successfully executed.</info>",
                $migration->getState()->getName()
            ));
        }
 
        if (!$found) {
            $io->error('No outstanding migrations were found.');
        }

        return self::SUCCESS;
    }
}
