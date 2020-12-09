<?php

namespace Illuminate\Database\Console;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

class RunSqlCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'db:run-sql';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Executes arbitrary SQL directly from the command line';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (! $this->confirmToProceed()) {
            return 1;
        }

        $database = $this->input->getOption('database');
        $sql = $this->input->getOption('sql');
        $forceFetch = $this->input->getOption('force-fetch');

        assert(is_string($sql));

        /** @var ConnectionInterface $connection */
        $connection = $this->laravel['db']->connection($database);

        if (Str::startsWith($sql, 'select') || $forceFetch) {
            $items = $connection->select($sql);
            $items = array_map(function ($item) {
                return (array) $item;
            }, $items);
            $headers = empty($items) ? [] : array_keys($items[0]);
            $this->output->table($headers, $items);
        } else {
            $this->output->write($connection->statement($sql));
        }

        return 0;
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use'],
            ['sql', null, InputOption::VALUE_REQUIRED, 'The SQL statement to execute'],
            ['force-fetch', null, InputOption::VALUE_NONE, 'Forces fetching the result'],
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production'],
        ];
    }
}
