<?php

namespace Illuminate\Queue\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\Factory;
use Illuminate\Queue\Events\QueueBusy;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'queue:monitor')]
class MonitorCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'queue:monitor
                       {queues : The names of the queues to monitor}
                       {--max=1000 : The maximum number of jobs that can be on the queue before an event is dispatched}';

    /**
     * The name of the console command.
     *
     * This name is used to identify the command during lazy loading.
     *
     * @var string|null
     *
     * @deprecated
     */
    protected static $defaultName = 'queue:monitor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor the size of the specified queues';

    /**
     * The queue manager instance.
     *
     * @var \Illuminate\Contracts\Queue\Factory
     */
    protected $manager;

    /**
     * The events dispatcher instance.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * The table headers for the command.
     *
     * @var string[]
     */
    protected $headers = ['Connection', 'Queue', 'Size', 'Status'];

    /**
     * Create a new queue listen command.
     *
     * @param  \Illuminate\Contracts\Queue\Factory  $manager
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @return void
     */
    public function __construct(Factory $manager, Dispatcher $events)
    {
        parent::__construct();

        $this->manager = $manager;
        $this->events = $events;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $queues = $this->parseQueues($this->argument('queues'));

        $this->displaySizes($queues);

        $this->dispatchEvents($queues);
    }

    /**
     * Parse the queues into an array of the connections and queues.
     *
     * @param  string  $queues
     * @return \Illuminate\Support\Collection
     */
    protected function parseQueues($queues)
    {
        return collect(explode(',', $queues))->map(function ($queue) {
            [$connection, $queue] = array_pad(explode(':', $queue, 2), 2, null);

            if (! isset($queue)) {
                $queue = $connection;
                $connection = $this->laravel['config']['queue.default'];
            }

            return [
                'connection' => $connection,
                'queue' => $queue,
                'size' => $size = $this->manager->connection($connection)->size($queue),
                'status' => $size >= $this->option('max') ? '<fg=red>ALERT</>' : 'OK',
            ];
        });
    }

    /**
     * Display the failed jobs in the console.
     *
     * @param  \Illuminate\Support\Collection  $queues
     * @return void
     */
    protected function displaySizes(Collection $queues)
    {
        $this->table($this->headers, $queues);
    }

    /**
     * Fire the monitoring events.
     *
     * @param  \Illuminate\Support\Collection  $queues
     * @return void
     */
    protected function dispatchEvents(Collection $queues)
    {
        foreach ($queues as $queue) {
            if ($queue['status'] == 'OK') {
                continue;
            }

            $this->events->dispatch(
                new QueueBusy(
                    $queue['connection'],
                    $queue['queue'],
                    $queue['size'],
                )
            );
        }
    }
}
