<?php

namespace Illuminate\Database\Console;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\View;
use Doctrine\DBAL\VersionAwarePlatformDriver;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Support\ConfigurationUrlParser;
use Symfony\Component\Console\Output\OutputInterface;
use UnexpectedValueException;

class ShowCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:show {--database= : The database to use}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show information about the database';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(ConnectionResolverInterface $connections)
    {
        $connection = $connections->connection($database = $this->input->getOption('database'));

        $schema = $connection->getDoctrineSchemaManager();

        $tables = collect($schema->listTables())->map(fn (Table $table, $index) => [
            'id' => $index + 1,
            'table' => $table->getName(),
            'size' => $this->getTableSize($connection, $table->getName()),
            'rows' => $connection->table($table->getName())->count(),
            'engine' => rescue(fn() => $table->getOption('engine')),
        ]);
        $views = collect($schema->listViews())->reject(fn (View $view) => str($view->getName())->startsWith(['pg_catalog', 'information_schema']));

        $this->newLine();

        $platform = $this->getPlatformName($schema->getDatabasePlatform());
        $connectionCount = $this->getConnectionCount($connection);

        $this->components->twoColumnDetail('<fg=green;options=bold>'.$platform.'</>');
        $this->components->twoColumnDetail('Open Connections', $connectionCount);
        $this->components->twoColumnDetail('Total Tables', $tables->count());
        $this->components->twoColumnDetail('Total Size', number_format($tables->sum('size') / 1024 / 1024, 2) . 'Mb');

        $this->newLine();

        if ($tables->isNotEmpty()) {
            $this->components->twoColumnDetail('<fg=green;options=bold>Table</>', 'Size (Mb) / <fg=yellow;options=bold>Rows</>');

            $tables->each(function ($table) {
                $this->components->twoColumnDetail(
                    $table['table']. ' <fg=gray>' . $table['engine'].'</>',
                    number_format($table['size'] / 1024 / 1024, 2).' / <fg=yellow;options=bold>'.number_format($table['rows']).'</>'
                );
            });

            $this->newLine();
        }

        if ($views->isNotEmpty()) {
            $this->components->twoColumnDetail('<fg=green;options=bold>View</>', '<fg=green;options=bold>Rows</>');

            $views->map(fn (View $view) => [
                $view->getName(),
                $connection->table($view->getName())->count(),
            ])
            ->each(fn ($view) => $this->components->twoColumnDetail($view[0], number_format($view[1])));

            $this->newLine();
        }

//        $answer = $this->components->choice('Which database do you want to inspect?', $tables->pluck('table', 'id')->values()->all());

        $databaseConnection = $this->getConfigFromDatabase($database);

        dd($databaseConnection);

        $this->components->twoColumnDetail('<fg=green;options=bold>Connection</>');
        $this->components->twoColumnDetail('Host', $schema->get);

        return 0;
    }

    protected function getPlatformName(AbstractPlatform $platform)
    {
        return match(class_basename($platform)) {
            'MySQLPlatform' => 'MySQL <= 5',
            'MySQL57Platform' => 'MySQL 5.7',
            'MySQL80Platform' => 'MySQL 8',
            'PostgreSQL100Platform', 'PostgreSQLPlatform' => 'Postgres',
            'SqlitePlatform' => 'SQLite',
            'SQLServerPlatform' => 'SQL Server',
        };
    }

    protected function getTableSize(ConnectionInterface $connection, string $table)
    {
        return match(class_basename($connection)) {
            'MySqlConnection' => $this->getMySQLTableSize($connection, $table),
//            'PostgresConnection' => $this->getPgsqlTableSize($connection, $table),
            'SqliteConnection' => $this->getSqliteTableSize($connection, $table),
            default => null,
        };
    }

    protected function getMySQLTableSize(ConnectionInterface $connection, string $table)
    {
        return $connection->selectOne('SELECT (data_length + index_length) AS size FROM information_schema.TABLES WHERE table_schema = ? AND table_name = ?', [
            $connection->getDatabaseName(),
            $table,
        ])->size;
    }

    protected function getSqliteTableSize(ConnectionInterface $connection, string $table)
    {
        return $connection->selectOne('SELECT SUM(pgsize) FROM dbstat WHERE name=?', [
            $table,
        ])->size;
    }

    protected function getConnectionCount(ConnectionInterface $connection)
    {
        return match(class_basename($connection)) {
            'MySqlConnection' => $this->getMySQLConnectionCount($connection),
            'PostgresConnection' => $this->getPgsqlConnectionCount($connection),
            default => null,
        };
    }

    protected function getPgsqlConnectionCount(ConnectionInterface $connection)
    {
        return $connection->selectOne('select count(*) as connections from pg_stat_activity')->connections;
    }

    protected function getMySQLConnectionCount(ConnectionInterface $connection)
    {
        return $connection->selectOne($connection->raw('show status where variable_name = "threads_connected"'))->Value;
    }

    protected function getConfigFromDatabase($database)
    {
        return config('database.connections.'.$database);
    }
}
