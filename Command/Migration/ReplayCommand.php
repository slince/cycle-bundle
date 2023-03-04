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
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'cycle:migration:replay', description: 'Replay (down, up) one or multiple migrations')]
final class ReplayCommand extends AbstractCommand
{
    protected function configure()
    {
        parent::configure();
        $this->addOption('all', 'a', InputOption::VALUE_NONE, 'Replay all migrations.');
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->verifyEnvironment($input, $io)) {
            //Making sure we can safely migrate in this environment
            return self::FAILURE;
        }

        $rollback = ['--force' => true];
        $migrate = ['--force' => true];

        if ($input->getOption('all')) {
            $rollback['--all'] = true;
        } else {
            $migrate['--one'] = true;
        }

        $io->writeln('Rolling back executed migration(s)...');
        $this->getApplication()->find('cycle:migration:rollback')->run(new ArrayInput($rollback), $output);

        $io->writeln('');

        $io->writeln('Executing outstanding migration(s)...');
        $this->getApplication()->find('cycle:migration:migrate')->run(new ArrayInput($migrate), $output);

        return self::SUCCESS;
    }
}
