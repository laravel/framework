<?php

namespace Illuminate\Database\Console;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;

class CreateCommand extends Command
{
    use ConfirmableTrait;

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
    protected $description = 'Create database';

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

        $config = $this->laravel['db']->getConfig();

        $connector = $this->laravel['db.factory']->createConnector($config);

        [$username, $password] = [
            $config['username'] ?? null, $config['password'] ?? null,
        ];

        $dsnWithoudDbName = str_replace("dbname={$config['database']}", '', $connector->getDsn($config));

        $pdo = $connector->createPdoConnection($dsnWithoudDbName, $username, $password, $connector->getOptions($config));

        $pdo->exec('CREATE DATABASE `' . $config['database'] .'`');

        $this->info('Database created successfully!');
    }
}
