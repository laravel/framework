<?php

namespace Illuminate\Database\Console;

use Illuminate\Console\Command;
use Illuminate\Support\ConfigurationUrlParser;
use Illuminate\Support\Uri;
use PDO;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use UnexpectedValueException;

#[AsCommand(name: 'db')]
class DbCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db {connection? : The database connection that should be used}
               {--read : Connect to the read connection}
               {--write : Connect to the write connection}
               {--open : Open the connection URL in a GUI client}';

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

        if (! isset($connection['host']) && $connection['driver'] !== 'sqlite') {
            $this->components->error('No host specified for this database connection.');
            $this->line('  Use the <options=bold>[--read]</> and <options=bold>[--write]</> options to specify a read or write connection.');
            $this->newLine();

            return Command::FAILURE;
        }

        if ($this->option('open')) {
            return $this->openDatabaseUrl($connection);
        }

        try {
            (new Process(
                array_merge([$command = $this->getCommand($connection)], $this->commandArguments($connection)),
                null,
                $this->commandEnvironment($connection)
            ))->setTimeout(null)->setTty(true)->mustRun(function ($type, $buffer) {
                $this->output->write($buffer);
            });
        } catch (ProcessFailedException $e) {
            throw_unless($e->getProcess()->getExitCode() === 127, $e);

            $this->error("{$command} not found in path.");

            return Command::FAILURE;
        }

        return 0;
    }

    /**
     * Get the database connection configuration.
     *
     * @return array
     *
     * @throws \UnexpectedValueException
     */
    public function getConnection()
    {
        $connection = $this->laravel['config']['database.connections.'.
            (($db = $this->argument('connection')) ?? $this->laravel['config']['database.default'])
        ];

        if (empty($connection)) {
            throw new UnexpectedValueException("Invalid database connection [{$db}].");
        }

        if (! empty($connection['url'])) {
            $connection = (new ConfigurationUrlParser)->parseConfiguration($connection);
        }

        if ($this->option('read')) {
            if (is_array($connection['read']['host'])) {
                $connection['read']['host'] = $connection['read']['host'][0];
            }

            $connection = array_merge($connection, $connection['read']);
        } elseif ($this->option('write')) {
            if (is_array($connection['write']['host'])) {
                $connection['write']['host'] = $connection['write']['host'][0];
            }

            $connection = array_merge($connection, $connection['write']);
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
            'mariadb' => 'mariadb',
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
        $optionalArguments = [
            'password' => '--password='.$connection['password'],
            'unix_socket' => '--socket='.($connection['unix_socket'] ?? ''),
            'charset' => '--default-character-set='.($connection['charset'] ?? ''),
        ];

        if (! $connection['password']) {
            unset($optionalArguments['password']);
        }

        return array_merge([
            '--host='.$connection['host'],
            '--port='.$connection['port'],
            '--user='.$connection['username'],
        ], $this->getOptionalArguments($optionalArguments, $connection), [$connection['database']]);
    }

    /**
     * Get the arguments for the MariaDB CLI.
     *
     * @param  array  $connection
     * @return array
     */
    protected function getMariaDbArguments(array $connection)
    {
        return $this->getMysqlArguments($connection);
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
            'trust_server_certificate' => ['-C'],
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

    /**
     * Open the database connection URL in a GUI client.
     *
     * @param  array  $connection
     * @return int
     */
    protected function openDatabaseUrl(array $connection)
    {
        $this->open($this->buildDatabaseUrl($connection));

        return Command::SUCCESS;
    }

    /**
     * Build the database connection URL.
     *
     * @param  array  $connection
     * @return string
     */
    protected function buildDatabaseUrl(array $connection)
    {
        $driver = $this->getDriverScheme($connection['driver']);

        if ($connection['driver'] === 'sqlite') {
            return $connection['database'];
        }

        return (new Uri)
            ->withScheme($driver)
            ->withHost($connection['host'])
            ->withUser(
                $connection['username'] ?? null,
                ! empty($connection['password']) ? $connection['password'] : null,
            )
            ->when(! empty($connection['port']), fn (Uri $uri) => $uri->withPort((int) $connection['port']))
            ->when(! empty($connection['database']), fn (Uri $uri) => $uri->withPath($connection['database']))
            ->when(! empty($this->getQueryParameters($connection)), fn (Uri $uri) => $uri->withQuery($this->getQueryParameters($connection)))
            ->value();
    }

    /**
     * Get the URL scheme for the database driver.
     *
     * @param  string  $driver
     * @return string
     */
    protected function getDriverScheme($driver)
    {
        return match ($driver) {
            'mysql', 'mariadb' => 'mysql',
            'pgsql' => 'postgresql',
            'sqlite' => 'sqlite',
            'sqlsrv' => 'sqlserver',
            default => $driver,
        };
    }

    /**
     * Get additional query parameters for the URL.
     *
     * @param  array  $connection
     * @return array
     */
    protected function getQueryParameters(array $connection)
    {
        $params = [];

        // Add SSL/TLS parameters if configured
        if (! empty($connection['sslmode'])) {
            $params['sslmode'] = $connection['sslmode'];
        }

        if (! empty($connection['options'])) {
            // For PostgreSQL SSL mode
            if (isset($connection['options'][PDO::MYSQL_ATTR_SSL_CA])) {
                $params['ssl'] = 'true';
            }
        }

        return $params;
    }

    /**
     * Open the database URL.
     *
     * @param  string  $url
     * @return void
     */
    protected function open($url)
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $process = Process::fromShellCommandline(escapeshellcmd("start {$url}"));
            $process->run();

            if (! $process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            return;
        }

        $binary = collect(match (PHP_OS_FAMILY) {
            'Darwin' => ['open'],
            'Linux' => ['xdg-open', 'wslview'],
        })->first(fn ($binary) => (new ExecutableFinder)->find($binary) !== null);

        if ($binary === null) {
            $this->components->warn('Unable to open the URL on your system. You will need to open it yourself.');
            $this->components->info("Database URL: {$url}");

            return;
        }

        $process = Process::fromShellCommandline(escapeshellcmd("{$binary} {$url}"));
        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }
}
