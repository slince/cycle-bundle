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

use Slince\CycleBundle\Command\Migration\AbstractCommand;
use Slince\CycleBundle\Command\Schema\Generator\ShowChanges;
use Slince\CycleBundle\Schema\Registry\RegistryFactory;
use Slince\CycleBundle\Schema\SchemaManager;
use Cycle\Migrations\Config\MigrationConfig;
use Cycle\Migrations\Migrator;
use Cycle\Migrations\State;
use Cycle\Schema\Generator\Migrations\GenerateMigrations;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'cycle:schema:migrate', description: 'Generate ORM schema migrations')]
final class MigrateCommand extends AbstractCommand
{
    protected SchemaManager $schemaManager;
    protected GenerateMigrations $migrations;
    protected RegistryFactory $registryFactory;

    public function __construct(SchemaManager $schemaManager, RegistryFactory $registryFactory, GenerateMigrations $migrations, Migrator $migrator, MigrationConfig $config)
    {
        parent::__construct($migrator, $config);

        $this->schemaManager = $schemaManager;
        $this->registryFactory = $registryFactory;
        $this->migrations = $migrations;
    }

    protected function configure()
    {
        $this->addOption('run', 'r', InputOption::VALUE_NONE, 'Automatically run generated migration.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->migrator->configure();

        $io = new SymfonyStyle($input, $output);

        foreach ($this->migrator->getMigrations() as $migration) {
            if ($migration->getState()->getStatus() !== State::STATUS_EXECUTED) {
                $io->error('Outstanding migrations found, run `cycle:migration:migrate` first.');
                return self::SUCCESS;
            }
        }

        $registry = $this->registryFactory->create();

        $show = new ShowChanges($output);
        $this->schemaManager->compile($registry, [$show]);

        if ($show->hasChanges()) {
            $this->schemaManager->compileWith($registry, [$this->migrations]);

            if ($input->getOption('run')) {
                $this->getApplication()->find('cycle.migration.migrate')->run($input, $output);
            }
        }
        return self::SUCCESS;
    }
}
