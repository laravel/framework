<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Attributes\AsOptimize;
use Illuminate\Console\Command;
use ReflectionAttribute;
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

        $commands = collect($this->getApplication()->all())
            ->mapWithKeys(fn($command, $name) => [$name => collect((new \ReflectionClass($command))->getAttributes(AsOptimize::class))->first()])
            ->filter(fn(?ReflectionAttribute $attribute) => $attribute !== null)
            ->filter(fn(ReflectionAttribute $attribute) => ($attribute->getArguments()['clear']) === true)
            ->mapWithKeys(fn(ReflectionAttribute $attribute, $command) => [
                $attribute->getArguments()['name'] => fn() => $this->callSilent($command) == 0
            ]);

        $commands->each(fn($task, $description) => $this->components->task($description, $task));

        $this->newLine();
    }
}
