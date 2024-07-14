<?php

namespace Illuminate\Database\Console;

use Illuminate\Console\Command;
use Illuminate\Support\ConfigurationUrlParser;
use Symfony\Component\Console\Attribute\AsCommand;
use UnexpectedValueException;

#[AsCommand(name: 'db:sqlite-touch')]
class TouchCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:sqlite-touch {database? : The SQLite database connection that should be used
               {--force : Create the directory paths forcefully}
               {--safe : Do not fail if the connection is not SQLite}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates or touches a new SQLite database file';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $db = $this->argument('database') ?? $this->laravel['config']['database.default'];

        $connection = $this->getConnection($db);

        if ($connection['driver'] !== 'sqlite') {
            $message = "Database connection [{$db}] is not [sqlite].";

            if ($this->option('safe')) {
                return $this->line($message. ' Safely exiting as expected.');
            }

            throw new UnexpectedValueException($message);
        }

        $file = $connection['database'];

        $this->ensureDirectoryExists($file);

        $this->touch($file);

        $this->line("Database file [$file] touched.");
    }

    /**
     * Get the database connection configuration.
     *
     * @param  string  $db
     * @return array
     *
     * @throws \UnexpectedValueException
     */
    public function getConnection($db)
    {
        $connection = $this->laravel['config']["database.connections.$db"];

        if (empty($connection)) {
            throw new UnexpectedValueException("Invalid database connection [{$db}].");
        }

        return (new ConfigurationUrlParser)->parseConfiguration($connection);
    }

    /**
     * Ensure the directory, where the database file should be, exists in the filesystem.
     *
     * @param  string  $file
     * @return void
     */
    protected function ensureDirectoryExists($file)
    {
        $dir = pathinfo($file, PATHINFO_DIRNAME);

        if (! is_dir($dir)) {
            $this->option('force') ? @mkdir($dir, 0755, true) : mkdir($dir, 0755, true);
        }
    }

    /**
     * Touch the database file.
     *
     * @param  string  $file
     * @return void
     */
    protected function touch($file)
    {
        if (!touch($file)) {
            throw new UnexpectedValueException("Database file [$file] couldn't be touched.");
        }
    }
}
