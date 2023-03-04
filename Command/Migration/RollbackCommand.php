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

#[AsCommand(name: 'cycle:migration:rollback', description: 'Rollback one (default) or multiple migrations')]
final class RollbackCommand extends AbstractCommand
{
    protected function configure()
    {
        parent::configure();
        $this->addOption('all', 'a', InputOption::VALUE_NONE, 'Rollback all executed migrations.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->verifyEnvironment($input, $io)) {
            //Making sure we can safely migrate in this environment
            return self::FAILURE;
        }

        $this->migrator->configure();

        $found = false;
        $count = !$input->getOption('all') ? 1 : PHP_INT_MAX;
        while ($count > 0 && ($migration = $this->migrator->rollback())) {
            $found = true;
            $count--;
            $io->writeln(sprintf(
                "<info>Migration <comment>%s</comment> was successfully rolled back.</info>",
                $migration->getState()->getName()
            ));
        }

        if (!$found) {
            $io->error('No executed migrations were found.');
        }

        return self::SUCCESS;
    }
}
