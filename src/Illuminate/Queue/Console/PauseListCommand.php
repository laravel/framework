<?php

namespace Illuminate\Queue\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\Factory as QueueManager;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'queue:pause:list')]
class PauseListCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'queue:pause:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all currently paused queues';

    /**
     * The queue manager instance.
     *
     * @var \Illuminate\Contracts\Queue\Factory
     */
    protected $manager;

    /**
     * Create a new queue pause list command.
     *
     * @param  \Illuminate\Contracts\Queue\Factory  $manager
     */
    public function __construct(QueueManager $manager)
    {
        parent::__construct();

        $this->manager = $manager;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $pausedQueues = $this->manager->getPausedQueues();

        if (empty($pausedQueues)) {
            $this->components->info('No queues are currently paused.');

            return 0;
        }

        $this->components->info('Paused Queues:');

        $this->table(
            ['Queue Name'],
            collect($pausedQueues)->map(fn ($queue) => [$queue])->toArray()
        );

        return 0;
    }
}
