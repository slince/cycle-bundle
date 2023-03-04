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
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'cycle:migration:init', description: 'Init migrations component (create migrations table)')]
final class InitCommand extends AbstractCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->migrator->configure();
        $io->success('Migrations table were successfully created');

        return self::SUCCESS;
    }
}
