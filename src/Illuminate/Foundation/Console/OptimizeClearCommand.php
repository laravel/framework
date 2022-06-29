<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
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
     * The name of the console command.
     *
     * This name is used to identify the command during lazy loading.
     *
     * @var string|null
     *
     * @deprecated
     */
    protected static $defaultName = 'optimize:clear';

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
        $this->tasks('Removing cached bootstrap files', [
            'events' => fn () => $this->callSilent('event:clear'),
            'views' => fn () => $this->callSilent('view:clear'),
            'cache' => fn () => $this->callSilent('cache:clear'),
            'route' => fn () => $this->callSilent('route:clear'),
            'config' => fn () => $this->callSilent('config:clear'),
            'compiled' => fn () => $this->callSilent('clear-compiled'),
        ]);
    }
}
