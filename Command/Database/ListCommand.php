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

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\Database;
use Cycle\Database\DatabaseManager;
use Cycle\Database\Driver\Driver;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'cycle:db:list', description: 'Get list of available databases, their tables and records count')]
final class ListCommand extends Command
{
    protected DatabaseConfig $databaseConfig;
    protected DatabaseManager $dbal;

    public function __construct(DatabaseConfig $databaseConfig, DatabaseManager $dbal)
    {
        parent::__construct();

        $this->databaseConfig = $databaseConfig;
        $this->dbal = $dbal;
    }

    protected function configure()
    {
        $this->addArgument('db', InputArgument::OPTIONAL, 'Database name');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getArgument('db')) {
            $databases = [$input->getArgument('db')];
        } else {
            $databases = \array_keys($this->databaseConfig->getDatabases());
        }

        $io = new SymfonyStyle($input, $output);

        if (empty($databases)) {
            $io->error('No databases found.');

            return self::SUCCESS;
        }

        $grid = $io->createTable();
        $grid->setHeaders([
            'Name (ID):',
            'Database:',
            'Driver:',
            'Prefix:',
            'Status:',
            'Tables:',
            'Count Records:',
        ]);

        foreach ($databases as $database) {
            $database = $this->dbal->database($database);

            /** @var Driver $driver */
            $driver = $database->getDriver();

            $header = [
                $database->getName(),
                $driver->getSource(),
                $driver->getType(),
                $database->getPrefix() ?: '<comment>---</comment>',
            ];

            try {
                $driver->connect();
            } catch (\Exception $exception) {
                $this->renderException($grid, $header, $exception);

                if ($database->getName() != end($databases)) {
                    $grid->addRow(new TableSeparator());
                }

                continue;
            }

            $header[] = '<info>connected</info>';
            $this->renderTables($grid, $header, $database);
            if ($database->getName() != end($databases)) {
                $grid->addRow(new TableSeparator());
            }
        }

        $grid->render();

        return self::SUCCESS;
    }

    private function renderException(Table $grid, array $header, \Throwable $exception): void
    {
        $grid->addRow(
            \array_merge(
                $header,
                [
                    "<fg=red>{$exception->getMessage()}</fg=red>",
                    '<comment>---</comment>',
                    '<comment>---</comment>',
                ]
            )
        );
    }

    /**
     * @param  Table  $grid
     * @param  array  $header
     * @param  Database  $database
     */
    private function renderTables(Table $grid, array $header, Database $database): void
    {
        foreach ($database->getTables() as $table) {
            $grid->addRow(
                array_merge(
                    $header,
                    [$table->getName(), number_format($table->count())]
                )
            );
            $header = ['', '', '', '', ''];
        }

        $header[1] && $grid->addRow(array_merge($header, ['no tables', 'no records']));
    }
}
