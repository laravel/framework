<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\suggest;

#[AsCommand(name: 'find')]
class FindCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'find';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find an Artisan command.';


    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $commands = collect(array_keys($this->getApplication()->all()))
            ->filter(fn (string $command) => $command !== $this->signature)
            ->values();

        $command = suggest(
            'Search for a command',
            options: $commands->toArray(),
            required: true,
            hint: 'Type parts of a command name to search for'
        );

        $this->call($command);

        return 0;
    }
}
