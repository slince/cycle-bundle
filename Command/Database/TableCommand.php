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

namespace Slince\CycleBundle\Command\Database;

use Cycle\Database\Database;
use Cycle\Database\DatabaseManager;
use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Exception\DBALException;
use Cycle\Database\Injection\FragmentInterface;
use Cycle\Database\Query\QueryParameters;
use Cycle\Database\Schema\AbstractColumn;
use Cycle\Database\Schema\AbstractTable;
use Cycle\Database\Table;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'cycle:db:table', description: 'Describe table schema of specific database')]
final class TableCommand extends Command
{
    private const SKIP = '<comment>---</comment>';

    protected DatabaseManager $dbal;

    public function __construct(DatabaseManager $dbal)
    {
        parent::__construct();

        $this->dbal = $dbal;
    }

    protected function configure()
    {
        $this->addArgument('table', InputArgument::REQUIRED, 'Table name')
            ->addOption('database', 'db', InputOption::VALUE_OPTIONAL, 'Source database', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $database = $this->dbal->database($input->getOption('database'));
        /** @var Table $table */
        $table = $database->table($input->getArgument('table'));
        $schema = $table->getSchema();

        if (! $schema->exists()) {
            throw new DBALException(
                "Table {$database->getName()}.{$input->getArgument('table')} does not exists."
            );
        }

        $io = new SymfonyStyle($input, $output);

        $io->title(sprintf(
            "\n<fg=cyan>Columns of </fg=cyan><comment>%s.%s</comment>:\n",
            $database->getName(),
            $input->getArgument('table')
        ));

        $this->describeColumns($io, $schema);

        if (! empty($indexes = $schema->getIndexes())) {
            $this->describeIndexes($io, $database, $indexes);
        }

        if (! empty($foreignKeys = $schema->getForeignKeys())) {
            $this->describeForeignKeys($input, $io, $database, $foreignKeys);
        }

        $io->writeln("");

        return self::SUCCESS;
    }

    protected function describeColumns(SymfonyStyle $io, AbstractTable $schema): void
    {
        $columnsTable = $io->createTable();
        $columnsTable->setHeaders(
            [
                'Column:',
                'Database Type:',
                'Abstract Type:',
                'PHP Type:',
                'Default Value:',
            ]
        );

        foreach ($schema->getColumns() as $column) {
            $name = $column->getName();

            if (\in_array($column->getName(), $schema->getPrimaryKeys(), true)) {
                $name = "<fg=magenta>{$name}</fg=magenta>";
            }

            $defaultValue = $this->describeDefaultValue($column, $schema->getDriver());

            $columnsTable->addRow(
                [
                    $name,
                    $this->describeType($column),
                    $this->describeAbstractType($column),
                    $column->getType(),
                    $defaultValue ?? self::SKIP,
                ]
            );
        }

        $columnsTable->render();
    }

    protected function describeIndexes(SymfonyStyle $io, Database $database, array $indexes): void
    {
        $io->writeln(sprintf(
            "\n<fg=cyan>Indexes of </fg=cyan><comment>%s.%s</comment>:\n",
            $database->getName(),
            $this->argument('table')
        ));

        $indexesTable = $io->createTable();
        $indexesTable->setHeaders(['Name:', 'Type:', 'Columns:']);
        foreach ($indexes as $index) {
            $indexesTable->addRow(
                [
                    $index->getName(),
                    $index->isUnique() ? 'UNIQUE INDEX' : 'INDEX',
                    \implode(', ', $index->getColumns()),
                ]
            );
        }

        $indexesTable->render();
    }

    protected function describeForeignKeys(InputInterface $input, SymfonyStyle $io, Database $database, array $foreignKeys): void
    {
        $io->writeln(sprintf(
            "\n<fg=cyan>Foreign Keys of </fg=cyan><comment>%s.%s</comment>:\n",
            $database->getName(),
            $input->getArgument('table')
        ));
        $foreignTable = $io->createTable();
        $foreignTable->setHeaders(
            [
                'Name:',
                'Column:',
                'Foreign Table:',
                'Foreign Column:',
                'On Delete:',
                'On Update:',
            ]
        );

        foreach ($foreignKeys as $reference) {
            $foreignTable->addRow(
                [
                    $reference->getName(),
                    \implode(', ', $reference->getColumns()),
                    $reference->getForeignTable(),
                    \implode(', ', $reference->getForeignKeys()),
                    $reference->getDeleteRule(),
                    $reference->getUpdateRule(),
                ]
            );
        }

        $foreignTable->render();
    }

    protected function describeDefaultValue(AbstractColumn $column, DriverInterface $driver)
    {
        $defaultValue = $column->getDefaultValue();

        if ($defaultValue instanceof FragmentInterface) {
            $value = $driver->getQueryCompiler()->compile(new QueryParameters(), '', $defaultValue);

            return "<info>{$value}</info>";
        }

        if ($defaultValue instanceof \DateTimeInterface) {
            $defaultValue = $defaultValue->format('c');
        }

        return $defaultValue;
    }

    private function describeType(AbstractColumn $column): string
    {
        $type = $column->getType();

        $abstractType = $column->getAbstractType();

        if ($column->getSize()) {
            $type .= " ({$column->getSize()})";
        }

        if ($abstractType === 'decimal') {
            $type .= " ({$column->getPrecision()}, {$column->getScale()})";
        }

        return $type;
    }

    private function describeAbstractType(AbstractColumn $column): string
    {
        $abstractType = $column->getAbstractType();

        if (\in_array($abstractType, ['primary', 'bigPrimary'])) {
            $abstractType = "<fg=magenta>{$abstractType}</fg=magenta>";
        }

        return $abstractType;
    }
}

