<?php

namespace Illuminate\Foundation\Console;

use Exception;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Process\Process;

#[AsCommand(name: 'format')]
class FormatCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'format
                    {path? : The path to the file or directory to format}
                    {--test : Run Pint in test mode}
                    {--dirty : Only modify uncommitted files}';

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'format';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Format the application code using Laravel Pint';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $command = ['./vendor/bin/pint'];

            if ($this->option('test')) {
                $command[] = '--test';
            }

            if ($this->option('dirty')) {
                $command[] = '--dirty';
            }

            $path = $this->argument('path');
            if ($path) {
                $command[] = $path;
            }

            $process = new Process($command);
            $process->setTimeout(null);

            if (Process::isTtySupported()) {
                $process->setTty(true);
            }

            $process->run();

            return 0;
        } catch (Exception $e) {
            $this->components->error(sprintf(
                'Failed to format the application: %s.',
                $e->getMessage(),
            ));

            return 1;
        }
    }
}
