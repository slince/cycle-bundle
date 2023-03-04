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

use Slince\CycleBundle\Command\Schema\Generator\ShowChanges;
use Slince\CycleBundle\Schema\Registry\RegistryFactory;
use Slince\CycleBundle\Schema\SchemaManager;
use Cycle\Schema\Generator\SyncTables;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'cycle:schema:sync', description: 'Sync Cycle ORM schema with database without intermediate migration (risk operation)')]
final class SyncCommand extends Command
{
    protected SchemaManager $schemaManager;
    protected RegistryFactory $registryFactory;
    protected SyncTables $syncTables;

    public function __construct(SchemaManager $schemaManager, RegistryFactory $registryFactory, SyncTables $syncTables)
    {
        parent::__construct();

        $this->schemaManager = $schemaManager;
        $this->registryFactory = $registryFactory;
        $this->syncTables = $syncTables;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $registry = $this->registryFactory->create();

        $show = new ShowChanges($output);
        $this->schemaManager->compile($registry, [$show, $this->syncTables]);

        if ($show->hasChanges()) {
            $io->success("ORM Schema has been synchronized");
        }

        return self::SUCCESS;
    }
}
