<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'optimize:clear')]
class OptimizeClearCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'optimize:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove the cached bootstrap files';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->components->info('Clearing cached bootstrap files.');

        foreach ($this->getOptimizeClearTasks() as $description => $command) {
            $this->components->task($description, fn () => $this->callSilently($command) == 0);
        }

        $this->newLine();
    }

    /**
     * Get the commands that should be run to clear the "optimization" files.
     *
     * @return array
     */
    public function getOptimizeClearTasks()
    {
        return [
            'cache' => 'cache:clear',
            'compiled' => 'clear-compiled',
            'config' => 'config:clear',
            'events' => 'event:clear',
            'routes' => 'route:clear',
            'views' => 'view:clear',
            ...ServiceProvider::$optimizeClearCommands,
        ];
    }
}
