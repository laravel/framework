<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Support\Arr;
use Illuminate\Console\Command;

class ContainerBindingListCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'container:bindings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all registered container bindings';

    /**
     * The table headers for the command.
     *
     * @var array
     */
    protected $headers = ['ID'];

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $bindings = $this->getBindings();
        $this->displayBindings($bindings);
    }

    /**
     * Compile the bindings into a displayable format.
     *
     * @return array
     */
    protected function getBindings()
    {
        $bindings = $this->laravel->getBindings();

        ksort($bindings);

        return $this->pluckColumns($bindings);
    }

    /**
     * Remove unnecessary columns from the bindings.
     *
     * @param  array  $bindings
     * @return array
     */
    protected function pluckColumns(array $bindings)
    {
        return array_map(function ($id) use ($bindings) {
            $binding = array_merge($bindings[$id], ['id' => $id]);

            return Arr::only($binding, $this->getColumns());
        }, array_keys($bindings));
    }

    /**
     * Display the binding information on the console.
     *
     * @param  array  $bindings
     * @return void
     */
    protected function displayBindings(array $bindings)
    {
        $this->table($this->getHeaders(), $bindings);
    }

    /**
     * Get the table headers for the visible columns.
     *
     * @return array
     */
    protected function getHeaders()
    {
        return Arr::only($this->headers, array_keys($this->getColumns()));
    }

    /**
     * Get the column names to show (lowercase table headers).
     *
     * @return array
     */
    protected function getColumns()
    {
        $availableColumns = array_map('strtolower', $this->headers);

        return $availableColumns;
    }
}
