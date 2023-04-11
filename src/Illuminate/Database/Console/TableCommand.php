<?php

namespace Illuminate\Database\Console;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Table;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\select;

#[AsCommand(name: 'db:table')]
class TableCommand extends DatabaseInspectionCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:table
                            {table? : The name of the table}
                            {--database= : The database connection}
                            {--json : Output the table information as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display information about the given database table';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(ConnectionResolverInterface $connections)
    {
        if (! $this->ensureDependenciesExist()) {
            return 1;
        }

        $connection = $connections->connection($this->input->getOption('database'));

        $schema = $connection->getDoctrineSchemaManager();

        $this->registerTypeMappings($connection->getDoctrineConnection()->getDatabasePlatform());

        $table = $this->argument('table') ?: select(
            'Which table would you like to inspect?',
            collect($schema->listTables())->flatMap(fn (Table $table) => [$table->getName()])->toArray()
        );

        if (! $schema->tablesExist([$table])) {
            return $this->components->warn("Table [{$table}] doesn't exist.");
        }

        $table = $schema->introspectTable($table);

        $columns = $this->columns($table);
        $indexes = $this->indexes($table);
        $foreignKeys = $this->foreignKeys($table);

        $data = [
            'table' => [
                'name' => $table->getName(),
                'columns' => $columns->count(),
                'size' => $this->getTableSize($connection, $table->getName()),
            ],
            'columns' => $columns,
            'indexes' => $indexes,
            'foreign_keys' => $foreignKeys,
        ];

        $this->display($data);

        return 0;
    }

    /**
     * Get the information regarding the table's columns.
     *
     * @param  \Doctrine\DBAL\Schema\Table  $table
     * @return \Illuminate\Support\Collection
     */
    protected function columns(Table $table)
    {
        return collect($table->getColumns())->map(fn (Column $column) => [
            'column' => $column->getName(),
            'attributes' => $this->getAttributesForColumn($column),
            'default' => $column->getDefault(),
            'type' => $column->getType()->getName(),
        ]);
    }

    /**
     * Get the attributes for a table column.
     *
     * @param  \Doctrine\DBAL\Schema\Column  $column
     * @return \Illuminate\Support\Collection
     */
    protected function getAttributesForColumn(Column $column)
    {
        return collect([
            $column->getAutoincrement() ? 'autoincrement' : null,
            'type' => $column->getType()->getName(),
            $column->getUnsigned() ? 'unsigned' : null,
            ! $column->getNotNull() ? 'nullable' : null,
        ])->filter();
    }

    /**
     * Get the information regarding the table's indexes.
     *
     * @param  \Doctrine\DBAL\Schema\Table  $table
     * @return \Illuminate\Support\Collection
     */
    protected function indexes(Table $table)
    {
        return collect($table->getIndexes())->map(fn (Index $index) => [
            'name' => $index->getName(),
            'columns' => collect($index->getColumns()),
            'attributes' => $this->getAttributesForIndex($index),
        ]);
    }

    /**
     * Get the attributes for a table index.
     *
     * @param  \Doctrine\DBAL\Schema\Index  $index
     * @return \Illuminate\Support\Collection
     */
    protected function getAttributesForIndex(Index $index)
    {
        return collect([
            'compound' => count($index->getColumns()) > 1,
            'unique' => $index->isUnique(),
            'primary' => $index->isPrimary(),
        ])->filter()->keys()->map(fn ($attribute) => Str::lower($attribute));
    }

    /**
     * Get the information regarding the table's foreign keys.
     *
     * @param  \Doctrine\DBAL\Schema\Table  $table
     * @return \Illuminate\Support\Collection
     */
    protected function foreignKeys(Table $table)
    {
        return collect($table->getForeignKeys())->map(fn (ForeignKeyConstraint $foreignKey) => [
            'name' => $foreignKey->getName(),
            'local_table' => $table->getName(),
            'local_columns' => collect($foreignKey->getLocalColumns()),
            'foreign_table' => $foreignKey->getForeignTableName(),
            'foreign_columns' => collect($foreignKey->getForeignColumns()),
            'on_update' => Str::lower(rescue(fn () => $foreignKey->getOption('onUpdate'), 'N/A')),
            'on_delete' => Str::lower(rescue(fn () => $foreignKey->getOption('onDelete'), 'N/A')),
        ]);
    }

    /**
     * Render the table information.
     *
     * @param  array  $data
     * @return void
     */
    protected function display(array $data)
    {
        $this->option('json') ? $this->displayJson($data) : $this->displayForCli($data);
    }

    /**
     * Render the table information as JSON.
     *
     * @param  array  $data
     * @return void
     */
    protected function displayJson(array $data)
    {
        $this->output->writeln(json_encode($data));
    }

    /**
     * Render the table information formatted for the CLI.
     *
     * @param  array  $data
     * @return void
     */
    protected function displayForCli(array $data)
    {
        [$table, $columns, $indexes, $foreignKeys] = [
            $data['table'], $data['columns'], $data['indexes'], $data['foreign_keys'],
        ];

        $this->newLine();

        $this->components->twoColumnDetail('<fg=green;options=bold>'.$table['name'].'</>');
        $this->components->twoColumnDetail('Columns', $table['columns']);

        if ($size = $table['size']) {
            $this->components->twoColumnDetail('Size', number_format($size / 1024 / 1024, 2).'MiB');
        }

        $this->newLine();

        if ($columns->isNotEmpty()) {
            $this->components->twoColumnDetail('<fg=green;options=bold>Column</>', 'Type');

            $columns->each(function ($column) {
                $this->components->twoColumnDetail(
                    $column['column'].' <fg=gray>'.$column['attributes']->implode(', ').'</>',
                    ($column['default'] ? '<fg=gray>'.$column['default'].'</> ' : '').''.$column['type'].''
                );
            });

            $this->newLine();
        }

        if ($indexes->isNotEmpty()) {
            $this->components->twoColumnDetail('<fg=green;options=bold>Index</>');

            $indexes->each(function ($index) {
                $this->components->twoColumnDetail(
                    $index['name'].' <fg=gray>'.$index['columns']->implode(', ').'</>',
                    $index['attributes']->implode(', ')
                );
            });

            $this->newLine();
        }

        if ($foreignKeys->isNotEmpty()) {
            $this->components->twoColumnDetail('<fg=green;options=bold>Foreign Key</>', 'On Update / On Delete');

            $foreignKeys->each(function ($foreignKey) {
                $this->components->twoColumnDetail(
                    $foreignKey['name'].' <fg=gray;options=bold>'.$foreignKey['local_columns']->implode(', ').' references '.$foreignKey['foreign_columns']->implode(', ').' on '.$foreignKey['foreign_table'].'</>',
                    $foreignKey['on_update'].' / '.$foreignKey['on_delete'],
                );
            });

            $this->newLine();
        }
    }
}
