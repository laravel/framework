<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Contracts\Encryption\Encrypter as EncrypterContract;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder as SchemaContract;
use Illuminate\Support\DateFactory;
use InvalidArgumentException;

#[AsCommand(name: 'crypt:refresh')]
class CryptRefreshCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'crypt:refresh
                    {targets : The table name and columns to refresh, like "table:column,column..."}
                    {--connection : The database connection to use.}
                    {--flag-column=laravel_refreshed_at : The temporary column name to flag successfully refreshed columns. Setting it to "false" or "null" will disable it.}
                    {--id=id : The column ID to use for lazy chunking.}
                    {--chunk=1000 : The amount of items per chunk to process.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refreshes encrypted table columns with the current app encryption key';

    /**
     * Execute the console command.
     *
     * @param  \Illuminate\Contracts\Encryption\Encrypter  $encrypter
     * @return void
     */
    public function handle(EncrypterContract $encrypter, SchemaContract $schema, DateFactory $date)
    {
        $this->confirmToProceed();

        [$table, $columns, $id, $flagColumn] = $this->parseOptions();

        $query = $this->createQueryFor($table);

        $this->ensureFlagColumnShouldExist($schema, $table, $flagColumn);

        $this->withProgressBar(
            $this->getRowsLazily($query, $columns, $id, $flagColumn),
            function ($row) use ($date, $encrypter, $id, $columns, $query, $flagColumn) {
                $data = [];

                foreach ($columns as $column) {
                    if (is_string($row->{$column}) && $row->{$column}) {
                        $data[$column] = $encrypter->encrypt($encrypter->decrypt($row->{$column}, false), false);
                    }
                }

                if ($flagColumn) {
                    $data[$flagColumn] = $date->now();
                }

                $query->clone()->where($id, $row->{$id})->update($data);
            }
        );

        $this->removeFlagColumnIfExists($schema, $table, $flagColumn);
    }

    /**
     * Return the flag column name.
     *
     * @return string|false
     */
    protected function flagColumn()
    {
        $name = (string) $this->option('flag-column');

        if (in_array(strtolower($name), ['none', 'null', 'false', '0', ''], true)) {
            return false;
        }

        return $name;
    }

    /**
     * Return a configuration value from the app by its key.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    protected function config($key, $default = null)
    {
        return $this->laravel->make('config')->get($key, $default);
    }

    /**
     * Parse the options of the command.
     *
     * @return array{table: string, columns: string[], id: string, flagColumn: string|false }
     */
    protected function parseOptions()
    {
        [$table, $columns] = array_pad(explode(':', $this->argument('targets'), 2), 2, null);

        if (!$table) {
            throw new InvalidArgumentException('No table name was issued.');
        }

        $columns = array_filter(array_map('trim', explode(',', $columns)));

        if (!$columns) {
            throw new InvalidArgumentException('No columns were issued.');
        }

        return [$table, $columns, $this->option('id'), $this->flagColumn()];
    }

    /**
     * Create a lazy query for the target model
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string[]  $columns
     * @param  string  $id
     * @param  string|false  $flagColumn
     * @return \Illuminate\Support\LazyCollection<object>
     */
    protected function getRowsLazily($query, $columns, $id, $flagColumn)
    {
        return $query
            ->clone()
            ->select([$id, ...$columns])
            ->when($flagColumn)->whereNull($flagColumn)
            ->lazyById($this->option('chunk'), $id);
    }

    /**
     * Create a new Query Builder instance for the given table.
     *
     * @param  string $table
     */
    protected function createQueryFor($table)
    {
        return $this->laravel->make('db')->connection($this->option('connection'))->table($table);
    }

    /**
     * Ensure the target table has a column to skip successfully refreshed rows.
     *
     * @param  \Illuminate\Database\Schema\Builder  $schema
     * @param  string  $name
     * @param  string $column
     * @return void
     */
    protected function ensureFlagColumnShouldExist($schema, $name, $column)
    {
        if (!$column) {
            $this->warn("No flag column was issued to skip already refreshed rows.");

            return;
        }

        $this->info("Using <fg=blue>$column</> as flag column in <fg=blue>$name</> to refresh rows.");

        $schema->whenTableDoesntHaveColumn($name, $column, fn ($table) => $table->timestamp($column)->nullable());
    }

    /**
     * Removes the Flag Column if it exists on the target table.
     *
     * @param  \Illuminate\Database\Schema\Builder  $schema
     * @param  string  $name
     * @param  string  $column
     * @return void
     */
    protected function removeFlagColumnIfExists(SchemaContract $schema, mixed $name, mixed $column)
    {
        $schema->whenTableHasColumn($name, $column, fn(Blueprint $builder) => $builder->dropColumn($column));
    }
}
