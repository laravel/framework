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
                            {--except= : Queue names to exclude from resuming}';

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
        $except = (new Stringable($this->option('except') ?? ''))->explode(',')
            ->map(fn ($queue) => trim($queue));

        if ($this->input->hasParameterOption('--except') && $except->contains('')) {
            $this->components->error('The --except option requires a queue name.');

            return self::FAILURE;
        }

        $except = $except->filter()->unique()->values()->all();

        if ($this->option('all')) {
            $manager->resumeAll($except);

            if ($except) {
                $this->components->info('Job processing on all queues except ['.implode(', ', $except).'] across all connections has been resumed.');

                return self::SUCCESS;
            }

            $this->components->info('Job processing on all queues across all connections has been resumed.');

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

        $manager->resume($connection, $queue);

        $this->components->info("Job processing on queue [{$connection}:{$queue}] has been resumed.");

        return self::SUCCESS;
    }
}
