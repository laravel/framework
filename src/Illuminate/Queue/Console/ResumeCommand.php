<?php

namespace Illuminate\Queue\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\Factory as QueueManager;
use Illuminate\Queue\Console\Concerns\ParsesQueue;
use Illuminate\Support\Stringable;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'queue:resume', aliases: ['queue:continue'])]
class ResumeCommand extends Command
{
    use ParsesQueue;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'queue:resume
                            {queue? : The name of the queue that should resume processing}
                            {--all : Resume job processing for all queues on all connections}
                            {--exclude= : Queue names to exclude from resuming}';

    /**
     * The console command name aliases.
     *
     * @var list<string>
     */
    protected $aliases = ['queue:continue'];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resume job processing for a paused queue';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(QueueManager $manager): int
    {
        $excludedQueues = (new Stringable($this->option('exclude') ?? ''))->explode(',')
            ->map(fn ($queue) => trim($queue));

        if ($this->input->hasParameterOption('--exclude') && $excludedQueues->contains('')) {
            $this->components->error('The --exclude option requires a queue name.');

            return self::FAILURE;
        }

        $excludedQueues = $excludedQueues->filter()->unique()->values()->all();

        if ($this->option('all')) {
            $manager->resumeAll($excludedQueues);

            if ($excludedQueues) {
                $this->components->info('Job processing on all queues except ['.implode(', ', $excludedQueues).'] across all connections has been resumed.');

                return self::SUCCESS;
            }

            $this->components->info('Job processing on all queues across all connections has been resumed.');

            return self::SUCCESS;
        }

        if ($excludedQueues) {
            $this->components->error('The --exclude option may only be used with the --all option.');

            return self::FAILURE;
        }

        if (! $this->argument('queue')) {
            $this->components->error('A queue name is required unless the --all option is used.');

            return self::FAILURE;
        }

        [$connection, $queue] = $this->parseQueue($this->argument('queue'));

        $manager->resume($connection, $queue);

        $this->components->info("Job processing on queue [{$connection}:{$queue}] has been resumed.");

        return self::SUCCESS;
    }
}
