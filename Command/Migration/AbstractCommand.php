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

use Cycle\Migrations\Config\MigrationConfig;
use Cycle\Migrations\Migrator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AbstractCommand extends Command
{
    public function __construct(
        protected Migrator $migrator,
        protected MigrationConfig $config
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this->addOption('force', 's', InputOption::VALUE_NONE, 'Skip safe environment check');
    }

    protected function verifyConfigured(SymfonyStyle $io): bool
    {
        if (!$this->migrator->isConfigured()) {
            $io->writeln(
                "<fg=red>Migrations are not configured yet, run '<info>migrate:init</info>' first.</fg=red>"
            );

            return false;
        }

        return true;
    }

    /**
     * Check if current environment is safe to run migration.
     */
    protected function verifyEnvironment(InputInterface $input, SymfonyStyle $io): bool
    {
        if ($input->getOption('force') || $this->config->isSafe()) {
            //Safe to run
            return true;
        }

        $io->writeln('<fg=red>Confirmation is required to run migrations!</fg=red>');

        if (!$this->askConfirmation($io)) {
            $io->writeln('<comment>Cancelling operation...</comment>');

            return false;
        }

        return true;
    }


    protected function askConfirmation(SymfonyStyle $io): bool
    {
        return $io->confirm('<question>Would you like to continue?</question> ');
    }
}
