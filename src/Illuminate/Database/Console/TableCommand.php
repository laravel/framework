<?php

namespace Illuminate\Database\Console;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Support\Str;

class TableCommand extends ShowCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:table 
                            {table : Tha name of the table}
                            {--database : The database to use}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show information about the given database table';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(ConnectionResolverInterface $connections)
    {
        $connection = $connections->connection($database = $this->input->getOption('database'));

        $schema = $connection->getDoctrineSchemaManager();
        $table = $schema->listTableDetails($table = $this->argument('table'));

        $columns = collect($table->getColumns())->map(fn (Column $column) => [
            'column' => $column->getName(),
            'attributes' => $this->getAttributes($column),
            'default' => $column->getDefault(),
            'type' => $column->getType()->getName(),
        ]);

        $this->newLine();

        $this->components->twoColumnDetail('<fg=green;options=bold>' . $table->getName() . '</>');
        $this->components->twoColumnDetail('Columns', $columns->count());
        $this->components->twoColumnDetail('Size', number_format($this->getTableSize($connection, $table->getName()) / 1024 / 1024, 2) . 'Mb');

        $this->newLine();

        if ($columns->isNotEmpty()) {
            $this->components->twoColumnDetail('<fg=green;options=bold>Column</>', 'Type');

            $columns->each(function ($column) use ($table) {
                $this->components->twoColumnDetail(
                    $column['column'] . ' <fg=gray>' . $column['attributes'] . '</>',
                    ($column['default'] ? '<fg=gray>' . $column['default'] . '</> ' : '') . '' . $column['type'] . ''
                );
            });

            $this->newLine();
        }

        $indexes = collect($table->getIndexes());

        if ($indexes->isNotEmpty()) {
            $this->components->twoColumnDetail('<fg=green;options=bold>Indexes</>');

            $indexes->each(function ($index) {
                $columns = implode(', ', $index->getColumns());
                $this->components->twoColumnDetail(
                    "{$index->getName()} <fg=gray>{$columns}</>",
                    $this->getIndexAttributes($index)
                );
            });

            $this->newLine();
        }

        $foreignKeys = collect($table->getForeignKeys());

        if ($foreignKeys->isNotEmpty()) {
            $this->components->twoColumnDetail('<fg=green;options=bold>Foreign Keys</>', 'On Update / On Delete');

            $foreignKeys->each(function ($foreignKey) {
                $localKeys = implode(', ', $foreignKey->getLocalColumns());
                $foreignKeys = implode(', ', $foreignKey->getForeignColumns());

                $this->components->twoColumnDetail(
                    $foreignKey->getName() . " <fg=gray;options=bold>$localKeys reference $foreignKeys on {$foreignKey->getForeignTableName()}</>",
                    Str::lower($foreignKey->getOption('onUpdate') . ' / ' . $foreignKey->getOption('onDelete')),
                );
            });

            $this->newLine();
        }

        return 0;
    }

    public function getAttributes(Column $column)
    {
        return collect([
            $column->getAutoincrement() ? 'autoincrement' : null,
            'type' => $column->getType()->getName(),
            $column->getUnsigned() ? 'unsigned' : null,
            !$column->getNotNull() ? 'nullable' : null,
        ])->filter()->implode(', ');
    }

    protected function getIndexAttributes(Index $index)
    {
        return Str::lower(
            collect([
                'compound' => count($index->getColumns()) > 1,
                'unique' => $index->isUnique(),
                'primary' => $index->isPrimary(),
            ])->filter()->keys()->implode(', ')
        );
    }
}
