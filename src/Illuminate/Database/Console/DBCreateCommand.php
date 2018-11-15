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
     * The database connection
     * 
     * @var \Illuminate\Database\ConnectionInterface
    */
    protected $connection;

    /**
     * The default database for supported connections.
     *
     * @var array
     */
    protected $defaultDatabases = [
        'mysql' => 'mysql',
        'pgsql' => 'postgres',
        'sqlsrv' => 'master'
    ];

    /**
     * Create a new database seed command instance.
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
        $this->connection = $this->getConnection();

        $name = trim($this->input->getArgument('name'));

        $this->createDatabase($name);
        
        $this->info('Database created successfully');

        if ($this->confirm('Do you wish to persist the database name to env file?')) {
            $this->setDatabaseInEnvironmentFile($name);
        }

        if ($this->confirm('Do you wish to persist the database credentials to env file?')) {
            $this->setCredentialsInEnvironmentFile($this->getCredentials());
        }

        if ($this->option('migrate')) {
            $this->setDatabaseInConfig($name);

            $this->runMigrations();

            if ($this->option('seed')) {
                $this->runSeeder();
            }
        }
    }

    /**
     * Drop all of the database tables.
     *
     * @param  string  $database
     * @return void
     */
    protected function createDatabase($database)
    {
        if ($this->connection->getSchemaBuilder()->hasDatabase($database)) {
            $this->error("Database '$database' already exists");

            exit();
        }

        return $this->connection->getSchemaBuilder()->createDatabase($database);
    }

    /**
     * Get the database connection
     *
     * @return \Illuminate\Database\ConnectionInterface
     */
    protected function getConnection()
    {        
        if ($this->hasCredentials()) {
            $credentials = $this->getCredentials();

            $this->laravel['config']->set([
                $this->getConfigurationKey('database') => $this->getDefaultDatabase(),
                $this->getConfigurationKey('username') => $credentials['username'],
                $this->getConfigurationKey('password') => $credentials['password']
            ]);

            $this->resolver->purge($this->getDatabase());
        }
        
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
        return $this->input->getOption('database') ?: $this->laravel['config']['database.default'];
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
     * Set the credentials in the environment file.
     *
     * @param  array $credentials  
     * @return bool
     */
    protected function setCredentialsInEnvironmentFile($credentials)
    {
        $this->writeNewEnvironmentFileWith('username', $credentials['username']);
        $this->writeNewEnvironmentFileWith('password', $credentials['password']);

        return true;
    }

    /**
     * Set the database in the environment file.
     *
     * @param  string $name  
     * @return bool
     */
    protected function setDatabaseInEnvironmentFile($name)
    {
        $this->writeNewEnvironmentFileWith('database', $name);

        return true;
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
     * Run the database seeder command.
     * 
     * @param  string $name
     * @return void
     */
    protected function setDatabaseInConfig($name)
    {
        $this->laravel['config']->set([
            $this->getConfigurationKey('database') => $name,
        ]);

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