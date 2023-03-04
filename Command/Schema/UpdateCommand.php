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

namespace Slince\CycleBundle\Command\Schema;

use Slince\CycleBundle\Schema\SchemaManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'cycle:schema:update', description: 'Update (init) cycle schema from database and annotated classes')]
final class UpdateCommand extends Command
{
    protected SchemaManager $schemaManager;

    public function __construct(SchemaManager $schemaManager)
    {
        parent::__construct();

        $this->schemaManager = $schemaManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->writeln('Updating ORM schema... ');

        $this->schemaManager->clear("");
        $this->schemaManager->warmUp("");

        $io->success('done');

        return self::SUCCESS;
    }
}
