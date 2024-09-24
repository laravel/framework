<?php

namespace Illuminate\Database\Console;

use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Number;
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
        $connection = $connections->connection($this->input->getOption('database'));
        $schema = $connection->getSchemaBuilder();
        $tables = collect($schema->getTables())
            ->keyBy(fn ($table) => $table['schema'] ? $table['schema'].'.'.$table['name'] : $table['name'])
            ->all();

        $tableName = $this->argument('table') ?: select(
            'Which table would you like to inspect?',
            array_keys($tables)
        );

        $table = $tables[$tableName] ?? Arr::first($tables, fn ($table) => $table['name'] === $tableName);

        if (! $table) {
            $this->components->warn("Table [{$tableName}] doesn't exist.");

            return 1;
        }

        $tableName = ($table['schema'] ? $table['schema'].'.' : '').$this->withoutTablePrefix($connection, $table['name']);

        $columns = $this->columns($schema, $tableName);
        $indexes = $this->indexes($schema, $tableName);
        $foreignKeys = $this->foreignKeys($schema, $tableName);

        $data = [
            'table' => [
                'schema' => $table['schema'],
                'name' => $table['name'],
                'columns' => count($columns),
                'size' => $table['size'],
                'comment' => $table['comment'],
                'collation' => $table['collation'],
                'engine' => $table['engine'],
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
     * @param  \Illuminate\Database\Schema\Builder  $schema
     * @param  string  $table
     * @return \Illuminate\Support\Collection
     */
    protected function columns(Builder $schema, string $table)
    {
        return collect($schema->getColumns($table))->map(fn ($column) => [
            'column' => $column['name'],
            'attributes' => $this->getAttributesForColumn($column),
            'default' => $column['default'],
            'type' => $column['type'],
        ]);
    }

    /**
     * Get the attributes for a table column.
     *
     * @param  array  $column
     * @return \Illuminate\Support\Collection
     */
    protected function getAttributesForColumn($column)
    {
        return collect([
            $column['type_name'],
            $column['generation'] ? $column['generation']['type'] : null,
            $column['auto_increment'] ? 'autoincrement' : null,
            $column['nullable'] ? 'nullable' : null,
            $column['collation'],
        ])->filter();
    }

    /**
     * Get the information regarding the table's indexes.
     *
     * @param  \Illuminate\Database\Schema\Builder  $schema
     * @param  string  $table
     * @return \Illuminate\Support\Collection
     */
    protected function indexes(Builder $schema, string $table)
    {
        return collect($schema->getIndexes($table))->map(fn ($index) => [
            'name' => $index['name'],
            'columns' => collect($index['columns']),
            'attributes' => $this->getAttributesForIndex($index),
        ]);
    }

    /**
     * Get the attributes for a table index.
     *
     * @param  array  $index
     * @return \Illuminate\Support\Collection
     */
    protected function getAttributesForIndex($index)
    {
        return collect([
            $index['type'],
            count($index['columns']) > 1 ? 'compound' : null,
            $index['unique'] && ! $index['primary'] ? 'unique' : null,
            $index['primary'] ? 'primary' : null,
        ])->filter();
    }

    /**
     * Get the information regarding the table's foreign keys.
     *
     * @param  \Illuminate\Database\Schema\Builder  $schema
     * @param  string  $table
     * @return \Illuminate\Support\Collection
     */
    protected function foreignKeys(Builder $schema, string $table)
    {
        return collect($schema->getForeignKeys($table))->map(fn ($foreignKey) => [
            'name' => $foreignKey['name'],
            'columns' => collect($foreignKey['columns']),
            'foreign_schema' => $foreignKey['foreign_schema'],
            'foreign_table' => $foreignKey['foreign_table'],
            'foreign_columns' => collect($foreignKey['foreign_columns']),
            'on_update' => $foreignKey['on_update'],
            'on_delete' => $foreignKey['on_delete'],
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

        $this->components->twoColumnDetail('<fg=green;options=bold>'.($table['schema'] ? $table['schema'].'.'.$table['name'] : $table['name']).'</>', $table['comment'] ? '<fg=gray>'.$table['comment'].'</>' : null);
        $this->components->twoColumnDetail('Columns', $table['columns']);

        if (! is_null($table['size'])) {
            $this->components->twoColumnDetail('Size', Number::fileSize($table['size'], 2));
        }

        if ($table['engine']) {
            $this->components->twoColumnDetail('Engine', $table['engine']);
        }

        if ($table['collation']) {
            $this->components->twoColumnDetail('Collation', $table['collation']);
        }

        $this->newLine();

        if ($columns->isNotEmpty()) {
            $this->components->twoColumnDetail('<fg=green;options=bold>Column</>', 'Type');

            $columns->each(function ($column) {
                $this->components->twoColumnDetail(
                    $column['column'].' <fg=gray>'.$column['attributes']->implode(', ').'</>',
                    (! is_null($column['default']) ? '<fg=gray>'.$column['default'].'</> ' : '').$column['type']
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
                    $foreignKey['name'].' <fg=gray;options=bold>'.$foreignKey['columns']->implode(', ').' references '.$foreignKey['foreign_columns']->implode(', ').' on '.$foreignKey['foreign_table'].'</>',
                    $foreignKey['on_update'].' / '.$foreignKey['on_delete'],
                );
            });

            $this->newLine();
        }
    }
}
