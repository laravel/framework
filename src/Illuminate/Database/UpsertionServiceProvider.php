<?php

namespace Illuminate\Database;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Database\Console\Upsertions\UpsertCommand;
use Illuminate\Database\Console\Upsertions\UpsertionMakeCommand;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

class UpsertionServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected $commands = [
        'UpsertionMake' => UpsertionMakeCommand::class,
        'Upsert' => UpsertCommand::class,
    ];

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerCommands($this->commands);

        $this->createUpsertionsDirectory();
    }

    /**
     * Register the given commands.
     *
     * @param  array  $commands
     * @return void
     */
    protected function registerCommands(array $commands)
    {
        foreach ($commands as $commandName => $command) {
            $method = "register{$commandName}Command";

            if (method_exists($this, $method)) {
                $this->{$method}();
            } else {
                $this->app->singleton($command);
            }
        }

        $this->commands(array_values($commands));
    }

    /**
     * Create the upsertions directory.
     *
     * @return void
     */
    protected function createUpsertionsDirectory()
    {
        $path = database_path('/upsertions');

        if (! is_dir($path)) {
            File::makeDirectory($path);
        }
    }
}
