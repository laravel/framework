<?php

namespace Illuminate\Database\Console;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\View;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Support\Arr;

class ShowCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:show {--database= : The database connection to use}
                {--json : Output the database as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show information about a database';

    /**
     * Execute the console command.
     *
     * @param  \Illuminate\Database\ConnectionResolverInterface  $connections
     * @return int
     */
    public function handle(ConnectionResolverInterface $connections)
    {
        $connection = $connections->connection($database = $this->input->getOption('database'));
        $schema = $connection->getDoctrineSchemaManager();

        $tables = $this->collectTables($connection, $schema);
        $views = $this->collectViews($connection, $schema);

        $data = [
            'platform' => [
                'config' => $this->getConfigFromDatabase($database),
                'name' => $this->getPlatformName($schema->getDatabasePlatform(), $database),
                'open_connections' => $this->getConnectionCount($connection),
            ],
            'tables' => $tables,
            'views' => $views,
        ];

        $this->display($data);

        return 0;
    }

    /**
     * Collect the tables within the database.
     *
     * @param  \Illuminate\Database\ConnectionInterface  $connection
     * @param  \Doctrine\DBAL\Schema\AbstractSchemaManager  $schema
     * @return \Illuminate\Support\Collection
     */
    protected function collectTables(ConnectionInterface $connection, AbstractSchemaManager $schema)
    {
        return collect($schema->listTables())->map(fn (Table $table, $index) => [
            'table' => $table->getName(),
            'size' => $this->getTableSize($connection, $table->getName()),
            'rows' => $connection->table($table->getName())->count(),
            'engine' => rescue(fn() => $table->getOption('engine')),
            'comment' => $table->getComment(),
        ]);
    }

    /**
     * Collect the views within the database.
     *
     * @param  \Illuminate\Database\ConnectionInterface  $connection
     * @param  \Doctrine\DBAL\Schema\AbstractSchemaManager  $schema
     * @return \Illuminate\Support\Collection
     */
    protected function collectViews(ConnectionInterface $connection, AbstractSchemaManager $schema)
    {
        return collect($schema->listViews())
            ->reject(fn (View $view) => str($view->getName())
            ->startsWith(['pg_catalog', 'information_schema', 'spt_']))
            ->map(fn (View $view) => [
                'view' => $view->getName(),
                'rows' => $connection->table($view->getName())->count(),
            ]);
    }

    /**
     * Render the database information.
     *
     * @param  array  $data
     * @return void
     */
    protected function display(array $data)
    {
        $this->option('json') ? $this->displayJson($data) : $this->displayCli($data);
    }

    /**
     * Render the database information as JSON.
     *
     * @param  array  $data
     * @return void
     */
    protected function displayJson(array $data)
    {
        $this->output->writeln(json_encode($data));
    }

    /**
     * Render the database information for the CLI.
     *
     * @param  array  $data
     * @return void
     */
    protected function displayCli(array $data)
    {
        $platform = $data['platform'];
        $tables = $data['tables'];
        $views = $data['views'];

        $this->newLine();

        $this->components->twoColumnDetail('<fg=green;options=bold>'.$platform['name'].'</>');
        $this->components->twoColumnDetail('Database', Arr::get($platform['config'], 'database'));
        $this->components->twoColumnDetail('Host', Arr::get($platform['config'], 'host'));
        $this->components->twoColumnDetail('Port', Arr::get($platform['config'], 'port'));
        $this->components->twoColumnDetail('Username', Arr::get($platform['config'], 'username'));
        $this->components->twoColumnDetail('URL', Arr::get($platform['config'], 'url'));
        $this->components->twoColumnDetail('Open Connections', $platform['open_connections']);
        $this->components->twoColumnDetail('Tables', $tables->count());
        if ($tableSizeSum = $tables->sum('size')) {
            $this->components->twoColumnDetail('Total Size', number_format($tables->sum('size') / 1024 / 1024, 2) . 'Mb');
        }

        $this->newLine();

        if ($tables->isNotEmpty()) {
            $this->components->twoColumnDetail('<fg=green;options=bold>Table</>', 'Size (Mb) / <fg=yellow;options=bold>Rows</>');

            $tables->each(function ($table) {
                $tableSize = null;
                if ($tableSize = $table['size']) {

                }

                $this->components->twoColumnDetail(
                    $table['table']. ($this->output->isVerbose() ? ' <fg=gray>' . $table['engine'].'</>' : null),
                    number_format($table['size'] / 1024 / 1024, 2).' / <fg=yellow;options=bold>'.number_format($table['rows']).'</>'
                );

                if ($this->output->isVerbose()) {
                    if ($table['comment']) {
                        $this->components->bulletList([
                            $table['comment'],
                        ]);
                    }
                }
            });

            $this->newLine();
        }

        if ($views->isNotEmpty()) {
            $this->components->twoColumnDetail('<fg=green;options=bold>View</>', '<fg=green;options=bold>Rows</>');

            $views->each(fn ($view) => $this->components->twoColumnDetail($view['view'], number_format($view['rows'])));

            $this->newLine();
        }
    }

    /**
     * Get a human-readable platform name.
     *
     * @param  \Doctrine\DBAL\Platforms\AbstractPlatform  $platform
     * @param  string  $database
     * @return string
     */
    protected function getPlatformName(AbstractPlatform $platform, $database)
    {
        return match(class_basename($platform)) {
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
     * @return null
     */
    protected function getTableSize(ConnectionInterface $connection, string $table)
    {
        return match(class_basename($connection)) {
            'MySqlConnection' => $this->getMySQLTableSize($connection, $table),
            'PostgresConnection' => $this->getPgsqlTableSize($connection, $table),
            'SqliteConnection' => $this->getSqliteTableSize($connection, $table),
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
        return match(class_basename($connection)) {
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
     * @return mixed
     */
    protected function getPgsqlConnectionCount(ConnectionInterface $connection)
    {
        return $connection->selectOne('select count(*) as connections from pg_stat_activity')->connections;
    }

    /**
     * Get the number of open connections for a MySQL database.
     *
     * @param  \Illuminate\Database\ConnectionInterface  $connection
     * @return mixed
     */
    protected function getMySQLConnectionCount(ConnectionInterface $connection)
    {
        return $connection->selectOne($connection->raw('show status where variable_name = "threads_connected"'))->Value;
    }

    /**
     * Get the number of open connections for an SQL Server database.
     *
     * @param  \Illuminate\Database\ConnectionInterface  $connection
     * @return mixed
     */
    protected function getSqlServerConnectionCount(ConnectionInterface $connection)
    {
        return $connection->selectOne('SELECT COUNT(*) connections FROM sys.dm_exec_sessions WHERE status = ?', ['running'])->connections;
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
}
