<?php

namespace Illuminate\Database\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Database\ConnectionResolverInterface as Resolver;

class DBCreateCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'db:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a new database for this application';

    /**
     * The connection resolver instance.
     *
     * @var \Illuminate\Database\ConnectionResolverInterface
     */
    protected $resolver;

    /**
     * The default databases for supported connections.
     *
     * @var array
     */
    protected $defaultDatabases = [
        'mysql' => 'mysql',
        'pgsql' => 'postgres',
        'sqlsrv' => 'master'
    ];

    /**
     * The default ports for supported connections.
     *
     * @var array
     */
    protected $defaultPorts = [
        'mysql' => 3306,
        'pgsql' => 5432,
        'sqlsrv' => 1433
    ];

    /**
     * Create a new database create command instance.
     *
     * @param  \Illuminate\Database\ConnectionResolverInterface  $resolver
     * @return void
     */
    public function __construct(Resolver $resolver)
    {
        parent::__construct();

        $this->resolver = $resolver;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $name = trim($this->input->getArgument('name'));

        if (! $this->createDatabase($name)) {
            $this->error("Could not create database.");

            exit();
        }

        $this->info('Database created successfully.');

        if ($this->confirm('Do you wish to persist the database name to env file?')) {
            $this->updateEnvironmentFile([
                'database' => $this->isSQLiteDatabase() ? database_path($name.'.sqlite') : $name
            ]);
        }

        if ($this->hasCredentials() && ! $this->isSQLiteDatabase()) {
            if ($this->confirm('Do you wish to persist the database credentials to env file?')) {
                $this->updateEnvironmentFile($this->getCredentials());
            }
        }

        if ($this->option('migrate')) {
            $this->updateConfig([
                'database' => $this->isSQLiteDatabase() ? database_path($name.'.sqlite') : $name
            ]);

            $this->purgeConnection();

            $this->runMigrations();

            if ($this->option('seed')) {
                $this->runSeeder();
            }
        }
    }

    /**
     * Create the database.
     *
     * @param  string  $database
     * @return bool
     */
    protected function createDatabase($database)
    {
        if ($this->isSQLiteDatabase()) {
            return $this->createSQLiteDatabase($database);
        }

        $connection = $this->getConnection();

        if ($connection->getSchemaBuilder()->hasDatabase($database)) {
            $this->error("Database '$database' already exist.");

            exit();
        }

        return $connection->getSchemaBuilder()->createDatabase($database);
    }

    /**
     * Create the database.
     *
     * @param  string  $database
     * @return bool
     */
    protected function createSQLiteDatabase($database)
    {
       if (file_exists(database_path($database.'.sqlite'))) {
            $this->error("Database '$database' already exist.");

            exit();
       }

       return touch(database_path($database.'.sqlite'));
    }

    /**
     * Get the database connection.
     *
     * @return \Illuminate\Database\ConnectionInterface
     */
    protected function getConnection()
    {        
        if ($this->hasCredentials()) {
            $this->updateConfig($this->getCredentials());
        }

        $this->updateConfig([
            'database' => $this->getDefaultDatabase(),
            'port' => $this->getDefaultPort()
        ]);

        $this->purgeConnection();
        
        return $this->resolver->connection($this->getDatabase());
    }

    /**
     * Determine if the developer added database credentials.
     *
     * @return bool
     */
    protected function hasCredentials()
    {
        return $this->option('username')
            && ($this->option('password') !== NULL && $this->option('password') !== false);
    }

    /**
     * Get the database credentials from the command.
     *
     * @return array
     */
    protected function getCredentials()
    {
        return [
            'username' => $this->option('username'),
            'password' => $this->option('password')
        ];
    }

    /**
     * Get the name of the database connection to use.
     *
     * @return string
     */
    protected function getDatabase()
    {
        return strtolower($this->input->getOption('database')) ?: $this->laravel['config']['database.default'];
    }

    /**
     * Get the default database for the connection.
     *
     * @return string
     */
    protected function getDefaultDatabase()
    {
        return $this->defaultDatabases[$this->getDatabase()];
    }

    /**
     * Get the default port for the connection.
     *
     * @return int
     */
    protected function getDefaultPort()
    {
        return $this->defaultPorts[$this->getDatabase()];
    }

    /**
     * Determine if the database is an SQLite database.
     * 
     * @return bool
    */
    protected function isSQLiteDatabase()
    {
        return $this->getDatabase() === 'sqlite';
    }
    
    /**
     * Update the environment file with configuration.
     *
     * @param  array  $config  
     * @return void
     */
    protected function updateEnvironmentFile($config)
    {
        if (! is_array($config)) {
            return;
        }

        foreach ($config as $key => $value) {
            $this->writeNewEnvironmentFileWith($key, $value);
        }
    }

    /**
     * Write a new environment file with the given value.
     *
     * @param  string $key
     * @param  string $value
     * @return void
     */
    protected function writeNewEnvironmentFileWith($key, $value)
    {        
        file_put_contents($this->laravel->environmentFilePath(), preg_replace(
            "/^DB_{$key}=(.*)/mi",
            "DB_" . strtoupper($key) . "=$value",
            file_get_contents($this->laravel->environmentFilePath())
        ));
    }

    /**
     * Get the connection configuration key.
     *
     * @return string
     */
    protected function getConfigurationKey($key)
    {
        return 'database.connections.' . $this->getDatabase() . ".$key";
    }

    /**
     * Update the connections's configuration.
     *
     * @param  array  $config  
     * @return void
     */
    protected function updateConfig($config)
    {
        if (! is_array($config)) {
            return;
        }

        foreach ($config as $key => $value) {
            $this->laravel['config']->set([
                $this->getConfigurationKey($key) => $value,
            ]);
        }
    }

    /**
     * Disconnect from the database and remove from local cache.
     *
     * @return void
     */
    protected function purgeConnection()
    {
        $this->resolver->purge($this->getDatabase());
    }

    /**
     * Run the migrate command.
     *
     * @return void
     */
    protected function runMigrations()
    {
        $this->info('Migrating database...');

        $this->call('migrate', [
            '--database' => $this->getDatabase(),
            '--force' => true
        ]);
    }

    /**
     * Run the database seeder command.
     *
     * @return void
     */
    protected function runSeeder()
    {
        $this->info('Seeding database...');

        $this->call('db:seed', ['--force' => true]);
    }
    
    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the database'],
        ];
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

            ['username', 'u', InputOption::VALUE_OPTIONAL, 'The username for the connection'],

            ['password', 'p', InputOption::VALUE_OPTIONAL, 'The password for the connection', ''],

            ['migrate', null, InputOption::VALUE_NONE, 'Indicates if the database migrations should be run'],

            ['seed', null, InputOption::VALUE_NONE, 'Indicates if the seed task should be run'],
        ];
    }
}