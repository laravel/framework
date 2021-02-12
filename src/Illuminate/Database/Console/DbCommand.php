<?php

namespace Illuminate\Database\Console;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use UnexpectedValueException;

class DbCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db {connection? : The database connection that should be used}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start a new database CLI session';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $connection = $this->getConnection();

        (new Process(
            array_merge([$this->getCommand($connection)], $this->commandArguments($connection)),
            null,
            $this->commandEnvironment($connection)
        ))->setTimeout(null)->setTty(true)->mustRun(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        return 0;
    }

    /**
     * Get the database connection configuration.
     *
     * @return array
     */
    public function getConnection()
    {
        $connection = $this->laravel['config']['database.connections.'.
            (($db = $this->argument('connection')) ?? $this->laravel['config']['database.default'])
        ];

        if (empty($connection)) {
            throw new UnexpectedValueException("Invalid database connection [{$db}].");
        }

        return $connection;
    }

    /**
     * Get the arguments for the database client command.
     *
     * @param  array  $connection
     * @return array
     */
    public function commandArguments(array $connection)
    {
        $driver = ucfirst($connection['driver']);

        return $this->{"get{$driver}Arguments"}($connection);
    }

    /**
     * Get the environment variables for the database client command.
     *
     * @param  array  $connection
     * @return array|null
     */
    public function commandEnvironment(array $connection)
    {
        $driver = ucfirst($connection['driver']);

        if (method_exists($this, "get{$driver}Environment")) {
            return $this->{"get{$driver}Environment"}($connection);
        }

        return null;
    }

    /**
     * Get the database client command to run.
     *
     * @param  array  $connection
     * @return string
     */
    public function getCommand(array $connection)
    {
        return [
            'mysql' => 'mysql',
            'pgsql' => 'psql',
            'sqlite' => 'sqlite3',
            'sqlsrv' => 'sqlcmd',
        ][$connection['driver']];
    }

    /**
     * Get the arguments for the MySQL CLI.
     *
     * @param  array  $connection
     * @return array
     */
    protected function getMysqlArguments(array $connection)
    {
        return array_merge([
            '--host='.$connection['host'],
            '--port='.$connection['port'],
            '--user='.$connection['username'],
        ], $this->getOptionalArguments([
            'password' => '--password='.$connection['password'],
            'unix_socket' => '--socket='.$connection['unix_socket'],
            'charset' => '--default-character-set='.$connection['charset'],
        ], $connection), [$connection['database']]);
    }

    /**
     * Get the arguments for the Postgres CLI.
     *
     * @param  array  $connection
     * @return array
     */
    protected function getPgsqlArguments(array $connection)
    {
        return [$connection['database']];
    }

    /**
     * Get the arguments for the SQLite CLI.
     *
     * @param  array  $connection
     * @return array
     */
    protected function getSqliteArguments(array $connection)
    {
        return [$connection['database']];
    }

    /**
     * Get the arguments for the SQL Server CLI.
     *
     * @param  array  $connection
     * @return array
     */
    protected function getSqlsrvArguments(array $connection)
    {
        return array_merge(...$this->getOptionalArguments([
            'database' => ['-d', $connection['database']],
            'username' => ['-U', $connection['username']],
            'password' => ['-P', $connection['password']],
            'host' => ['-S', 'tcp:'.$connection['host']
                        .($connection['port'] ? ','.$connection['port'] : ''), ],
        ], $connection));
    }

    /**
     * Get the environment variables for the Postgres CLI.
     *
     * @param  array  $connection
     * @return array|null
     */
    protected function getPgsqlEnvironment(array $connection)
    {
        return array_merge(...$this->getOptionalArguments([
            'username' => ['PGUSER' => $connection['username']],
            'host' => ['PGHOST' => $connection['host']],
            'port' => ['PGPORT' => $connection['port']],
            'password' => ['PGPASSWORD' => $connection['password']],
        ], $connection));
    }

    /**
     * Get the optional arguments based on the connection configuration.
     *
     * @param  array  $args
     * @param  array  $connection
     * @return array
     */
    protected function getOptionalArguments(array $args, array $connection)
    {
        return array_values(array_filter($args, function ($key) use ($connection) {
            return ! empty($connection[$key]);
        }, ARRAY_FILTER_USE_KEY));
    }
}
