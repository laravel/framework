<?php

namespace Illuminate\Queue\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\Factory as QueueManager;
use Illuminate\Queue\Console\Concerns\ParsesQueue;
use Illuminate\Queue\Worker;
use Illuminate\Support\Stringable;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'queue:pause')]
class PauseCommand extends Command
{
    use ParsesQueue;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'queue:pause
                            {queue? : The name of the queue to pause}
                            {--all : Pause job processing for all queues on all connections}
                            {--except= : Queue names to exclude from pausing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pause job processing for a specific queue';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(QueueManager $manager): int
    {
        $except = (new Stringable($this->option('except') ?? ''))->explode(',')
            ->map(fn ($queue) => trim($queue));

        if (! Worker::$pausable) {
            $this->components->error('Queue pausing is currently disabled.');

            return self::FAILURE;
        }

        if ($this->input->hasParameterOption('--except') && $except->contains('')) {
            $this->components->error('The --except option requires a queue name.');

            return self::FAILURE;
        }

        $except = $except->filter()->unique()->values()->all();

        if ($this->option('all')) {
            $manager->pauseAll($except);

            if ($except) {
                $this->components->info('Job processing on all queues except ['.implode(', ', $except).'] across all connections has been paused.');

                return self::SUCCESS;
            }

            $this->components->info('Job processing on all queues across all connections has been paused.');

            return self::SUCCESS;
        }

        if ($except) {
            $this->components->error('The --except option may only be used with the --all option.');

            return self::FAILURE;
        }

        if (! $this->argument('queue')) {
            $this->components->error('A queue name is required unless the --all option is used.');

            return self::FAILURE;
        }

        [$connection, $queue] = $this->parseQueue($this->argument('queue'));

        $manager->pause($connection, $queue);

        $this->components->info("Job processing on queue [{$connection}:{$queue}] has been paused.");

        return self::SUCCESS;
    }
}
