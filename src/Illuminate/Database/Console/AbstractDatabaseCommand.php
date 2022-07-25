<?php

namespace Illuminate\Database\Console;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\PostgresConnection;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Support\Arr;
use Symfony\Component\Process\Exception\ProcessSignaledException;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;

abstract class AbstractDatabaseCommand extends Command
{
    /**
     * Get a human-readable platform name.
     *
     * @param  \Doctrine\DBAL\Platforms\AbstractPlatform  $platform
     * @param  string  $database
     * @return string
     */
    protected function getPlatformName(AbstractPlatform $platform, $database)
    {
        return match (class_basename($platform)) {
            'MySQLPlatform' => 'MySQL <= 5',
            'MySQL57Platform' => 'MySQL 5.7',
            'MySQL80Platform' => 'MySQL 8',
            'PostgreSQL100Platform', 'PostgreSQLPlatform' => 'Postgres',
            'SqlitePlatform' => 'SQLite',
            'SQLServerPlatform' => 'SQL Server',
            'SQLServer2012Platform' => 'SQL Server 2012',
            default => $database,
        };
    }

    /**
     * Get the size of a table in bytes.
     *
     * @param  \Illuminate\Database\ConnectionInterface  $connection
     * @param  string  $table
     * @return int|null
     */
    protected function getTableSize(ConnectionInterface $connection, string $table)
    {
        return match (true) {
            $connection instanceof MySqlConnection => $this->getMySQLTableSize($connection, $table),
            $connection instanceof PostgresConnection => $this->getPgsqlTableSize($connection, $table),
            $connection instanceof SQLiteConnection => $this->getSqliteTableSize($connection, $table),
            default => null,
        };
    }

    /**
     * Get the size of a MySQL table in bytes.
     *
     * @param  \Illuminate\Database\ConnectionInterface  $connection
     * @param  string  $table
     * @return mixed
     */
    protected function getMySQLTableSize(ConnectionInterface $connection, string $table)
    {
        return $connection->selectOne('SELECT (data_length + index_length) AS size FROM information_schema.TABLES WHERE table_schema = ? AND table_name = ?', [
            $connection->getDatabaseName(),
            $table,
        ])->size;
    }

    /**
     * Get the size of a Postgres table in bytes.
     *
     * @param  \Illuminate\Database\ConnectionInterface  $connection
     * @param  string  $table
     * @return mixed
     */
    protected function getPgsqlTableSize(ConnectionInterface $connection, string $table)
    {
        return $connection->selectOne('SELECT pg_total_relation_size(?) AS size;', [
            $table,
        ])->size;
    }

    /**
     * Get the size of a SQLite table in bytes.
     *
     * @param  \Illuminate\Database\ConnectionInterface  $connection
     * @param  string  $table
     * @return mixed
     */
    protected function getSqliteTableSize(ConnectionInterface $connection, string $table)
    {
        return $connection->selectOne('SELECT SUM(pgsize) FROM dbstat WHERE name=?', [
            $table,
        ])->size;
    }

    /**
     * Get the number of open connections for a database.
     *
     * @param  \Illuminate\Database\ConnectionInterface  $connection
     * @return null
     */
    protected function getConnectionCount(ConnectionInterface $connection)
    {
        return match (class_basename($connection)) {
            'MySqlConnection' => $this->getMySQLConnectionCount($connection),
            'PostgresConnection' => $this->getPgsqlConnectionCount($connection),
            'SqlServerConnection' => $this->getSqlServerConnectionCount($connection),
            default => null,
        };
    }

    /**
     * Get the number of open connections for a Postgres database.
     *
     * @param  \Illuminate\Database\ConnectionInterface  $connection
     * @return int
     */
    protected function getPgsqlConnectionCount(ConnectionInterface $connection)
    {
        return (int) $connection->selectOne('select count(*) as connections from pg_stat_activity')->connections;
    }

    /**
     * Get the number of open connections for a MySQL database.
     *
     * @param  \Illuminate\Database\ConnectionInterface  $connection
     * @return int
     */
    protected function getMySQLConnectionCount(ConnectionInterface $connection)
    {
        return (int) $connection->selectOne($connection->raw('show status where variable_name = "threads_connected"'))->Value;
    }

    /**
     * Get the number of open connections for an SQL Server database.
     *
     * @param  \Illuminate\Database\ConnectionInterface  $connection
     * @return int
     */
    protected function getSqlServerConnectionCount(ConnectionInterface $connection)
    {
        return (int) $connection->selectOne('SELECT COUNT(*) connections FROM sys.dm_exec_sessions WHERE status = ?', ['running'])->connections;
    }

    /**
     * Get the connection details from the configuration.
     *
     * @param  string  $database
     * @return array
     */
    protected function getConfigFromDatabase($database)
    {
        $database ??= config('database.default');

        return Arr::except(config('database.connections.'.$database), ['password']);
    }

    /**
     * Ensure the dependencies for the database commands are available.
     *
     * @return int|null
     */
    protected function ensureDependenciesExist()
    {
        if (! interface_exists('Doctrine\DBAL\Driver')) {
            if (! $this->components->confirm('Displaying model information requires the Doctrine DBAL (doctrine/dbal) package. Would you like to install it?')) {
                return 1;
            }

            return $this->installDependencies();
        }
    }

    /**
     * Install the command's dependencies.
     *
     * @return void
     *
     * @throws \Symfony\Component\Process\Exception\ProcessSignaledException
     */
    protected function installDependencies()
    {
        $command = collect($this->composer->findComposer())
            ->push('require doctrine/dbal')
            ->implode(' ');

        $process = Process::fromShellCommandline($command, null, null, null, null);

        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
            try {
                $process->setTty(true);
            } catch (RuntimeException $e) {
                $this->components->warn($e->getMessage());
            }
        }

        try {
            $process->run(fn ($type, $line) => $this->output->write($line));
        } catch (ProcessSignaledException $e) {
            if (extension_loaded('pcntl') && $e->getSignal() !== SIGINT) {
                throw $e;
            }
        }
    }
}
