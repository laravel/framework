<?php

namespace Illuminate\Database\Console;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\View;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Support\Arr;

class ShowCommand extends AbstractDatabaseCommand
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
        $this->ensureDependenciesExist();

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
            'engine' => rescue(fn () => $table->getOption('engine')),
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
            $this->components->twoColumnDetail('Total Size', number_format($tableSizeSum / 1024 / 1024, 2).'Mb');
        }

        $this->newLine();

        if ($tables->isNotEmpty()) {
            $this->components->twoColumnDetail('<fg=green;options=bold>Table</>', 'Size (Mb) <fg=gray;options=bold>/</> <fg=yellow;options=bold>Rows</>');

            $tables->each(function ($table) {
                if ($tableSize = $table['size']) {
                    $tableSize = number_format($tableSize / 1024 / 1024, 2);
                }

                $this->components->twoColumnDetail(
                    $table['table'].($this->output->isVerbose() ? ' <fg=gray>'.$table['engine'].'</>' : null),
                    ($tableSize ? $tableSize.' <fg=gray;options=bold>/</> ' : '— <fg=gray;options=bold>/</> ').'<fg=yellow;options=bold>'.number_format($table['rows']).'</>'
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
}
