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

namespace Slince\CycleBundle\Database;

use Cycle\Database\Config;
use Cycle\Database\Config\ConnectionConfig;
use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\Config\DriverConfig;
use Cycle\Database\Config\MySQL;
use Cycle\Database\Config\Postgres;
use Cycle\Database\Config\SQLite;
use Cycle\Database\Config\SQLServer;
use Cycle\Database\Driver;

final class DatabaseConfigFactory
{
    public function createDatabaseConfig(array $config, array $connections): DatabaseConfig
    {
        $parameters = [
            'default' => $config['default'],
            'alias' => $config['alias'] ?? [],
            'databases' => $config['databases'],
            'connections' => $connections
        ];
        return new DatabaseConfig($parameters);
    }

    public function createDriverConfig(array $config): DriverConfig
    {
        return match ($config['driver']){
            'mysql', 'mariadb' => $this->createMysqlDriverConfig($config),
            'sqlite' =>$this->createSQLiteDriverConfig($config),
            'postgres', 'postgresql' => $this->createPostgresDriverConfig($config),
            'sqlserver' => $this->createSQLServerDriverConfig($config)
        };
    }

    public function createConnectionConfig(array $config): ConnectionConfig
    {
        return match ($config['driver']) {
            'mysql', 'mariadb' =>$this->createMysqlConnectionConfig($config),
            'sqlite' =>$this->createSQLiteConnectionConfig($config),
            'postgres', 'postgresql' => $this->createPostgresConnectionConfig($config),
            'sqlserver' => $this->createSQLServerConnectionConfig($config)
        };
    }

    public function createMysqlDriverConfig(array $config): Config\MySQLDriverConfig
    {
        return new Config\MySQLDriverConfig(
            $this->createMysqlConnectionConfig($config),
            $config['driver_class'] ?: Driver\MySQL\MySQLDriver::class,
            $config['reconnect'],
            $config['timezone'],
            $config['query_cache'],
            $config['readonly_schema'],
            $config['readonly'],
            $config['driver_options']
        );
    }

    public function createSQLiteDriverConfig(array $config): Config\SQLiteDriverConfig
    {
        return new Config\SQLiteDriverConfig(
            $this->createSQLiteConnectionConfig($config),
            $config['driver_class'] ?: Driver\SQLite\SQLiteDriver::class,
            $config['reconnect'],
            $config['timezone'],
            $config['query_cache'],
            $config['readonly_schema'],
            $config['readonly'],
            $config['driver_options']
        );
    }

    public function createPostgresDriverConfig(array $config): Config\PostgresDriverConfig
    {
        return new Config\PostgresDriverConfig(
            $this->createPostgresConnectionConfig($config),
            $config['driver_class'] ?: Driver\Postgres\PostgresDriver::class,
            $config['reconnect'],
            $config['timezone'],
            $config['query_cache'],
            $config['readonly_schema'],
            $config['readonly'],
            $config['driver_options']
        );
    }

    public function createSQLServerDriverConfig(array $config): Config\SQLServerDriverConfig
    {
        return new Config\SQLServerDriverConfig(
            $this->createSQLServerConnectionConfig($config),
            $config['driver_class'] ?: Driver\SQLServer\SQLServerDriver::class,
            $config['reconnect'],
            $config['timezone'],
            $config['query_cache'],
            $config['readonly_schema'],
            $config['readonly'],
            $config['driver_options']
        );
    }

    public function createMysqlConnectionConfig(array $config): Mysql\ConnectionConfig
    {
        if (!empty($config['dsn'])) {
            return new MySQL\DsnConnectionConfig($config['dsn'], $config['user'], $config['password'], $config['options']);
        }
        if (!empty($config['unix_socket'])) {
            return new MySQL\SocketConnectionConfig($config['dbname'], $config['unix_socket'], $config['charset'], $config['user'], $config['password'], $config['options']);
        }
        return new MySQL\TcpConnectionConfig($config['dbname'], $config['host'], $config['port'], $config['charset'], $config['user'], $config['password'], $config['options']);
    }

    public function createSQLiteConnectionConfig(array $config): SQLite\ConnectionConfig
    {
        if (!empty($config['dsn'])) {
            return new SQLite\DsnConnectionConfig($config['dsn'], $config['options']);
        }
        if (!empty($config['path'])) {
            return new SQLite\FileConnectionConfig($config['path'], $config['options']);
        }
        if (!empty($config['memory'])) {
            return new SQLite\MemoryConnectionConfig($config['options']);
        }
        throw new \InvalidArgumentException('Invalid connection config for SQLite');
    }

    public function createPostgresConnectionConfig(array $config): Postgres\ConnectionConfig
    {
        if (!empty($config['dsn'])) {
            return new Postgres\DsnConnectionConfig($config['dsn'], $config['user'], $config['password'], $config['options']);
        }
        return new Postgres\TcpConnectionConfig($config['dbname'], $config['host'], $config['port'], $config['user'], $config['password'], $config['options']);
    }

    public function createSQLServerConnectionConfig(array $config): SQLServer\ConnectionConfig
    {
        if (!empty($config['dsn'])) {
            return new SQLServer\DsnConnectionConfig($config['dsn'], $config['user'], $config['password'], $config['options']);
        }
        return new SQLServer\TcpConnectionConfig(
            $config['dbname'],
            $config['host'],
            $config['port'],
            $config['app'],
            $config['pooling'],
            $config['encrypt'],
            $config['failover'],
            $config['timeout'],
            $config['mars'],
            $config['quoted'],
            $config['trace_file'],
            $config['trace'],
            $config['isolation'],
            $config['trust_server_certificate'],
            $config['wsid'],
            $config['user'],
            $config['password'],
            $config['options']
        );
    }
}