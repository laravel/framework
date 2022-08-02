<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

#[AsCommand(name: 'pint')]
class PintCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'pint';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Execute Laravel pint';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->components->info('Pint is running.');

        $process = new Process(['./vendor/bin/pint']);
        $process->run();

        if ($process->isSuccessful() === false) {
            throw new ProcessFailedException($process);
        }

        $this->output->write($process->getOutput());

        return 0;
    }
}
