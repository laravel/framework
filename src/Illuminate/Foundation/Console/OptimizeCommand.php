<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'optimize')]
class OptimizeCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'optimize';

    /**
     * The name of the console command.
     *
     * This name is used to identify the command during lazy loading.
     *
     * @var string|null
     *
     * @deprecated
     */
    protected static $defaultName = 'optimize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cache the framework bootstrap files';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (! $this->confirmToProceed('Application Not In Production', $this->confirmCallback())) {
            return 1;
        }

        $this->components->info('Caching the framework bootstrap files');

        collect([
            'config' => fn () => $this->callSilent('config:cache') == 0,
            'routes' => fn () => $this->callSilent('route:cache') == 0,
        ])->each(fn ($task, $description) => $this->components->task($description, $task));

        $this->newLine();

        return 0;
    }

    /**
     * Get the confirmation callback.
     *
     * @return \Closure
     */
    protected function confirmCallback()
    {
        return function () {
            return $this->getLaravel()->environment('production') === false;
        };
    }
}
