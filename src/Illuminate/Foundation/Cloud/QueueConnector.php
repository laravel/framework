<?php

namespace Illuminate\Foundation\Cloud;

use Illuminate\Foundation\Application;
use Illuminate\Queue\Connectors\ConnectorInterface;
use Illuminate\Queue\Events\JobQueued;
use Illuminate\Queue\Events\WorkerStopping;
use Illuminate\Queue\Worker;
use Illuminate\Queue\WorkerStopReason;

class QueueConnector implements ConnectorInterface
{
    /**
     * Reserved memory so that errors can emit events correctly on memory exhaustion.
     */
    private static ?string $reservedMemory = null;

    /**
     * Create a new instance.
     */
    public function __construct(
        protected ConnectorInterface $connector,
        protected Application $app,
    ) {
        //
    }

    /**
     * Establish a queue connection.
     */
    public function connect(array $config): Queue
    {
        $queue = new Queue(
            $this->connector->connect($config),
            $this->app[Events::class],
            $config,
        );

        $this->configureQueue($queue);

        if (! $this->app->runningConsoleCommand('queue:work')) {
            return $queue;
        }

        $this->configureWorker($queue);
        $this->configureFailedJobProvider($queue);

        return $queue;
    }

    /**
     * Configure the queue.
     */
    protected function configureQueue(Queue $queue): void
    {
        $this->app['events']->listen(fn (JobQueued $event) => $event->connectionName === $queue->getConnectionName()
            ? $queue->finishQueueingJob($event->queue)
            : null);
    }

    /**
     * Configure the queue worker.
     */
    protected function configureWorker(Queue $queue): void
    {
        Worker::$restartable = false;
        Worker::$pausable = false;

        $this->app['events']->listen(fn (WorkerStopping $event) => match ($event->reason) {
            WorkerStopReason::TimedOut => $queue->finishProcessingJob(default: 'released'),
            default => $queue->finishProcessingJob(),
        });

        static::$reservedMemory = str_repeat('x', 32768);

        register_shutdown_function(function () use ($queue) {
            static::$reservedMemory = null;

            if (! is_null($error = error_get_last()) && in_array($error['type'], [E_COMPILE_ERROR, E_CORE_ERROR, E_ERROR, E_PARSE])) {
                $queue->finishProcessingJob(default: 'released');
            }
        });
    }

    /**
     * Configure the failed job provider.
     */
    protected function configureFailedJobProvider(Queue $queue): void
    {
        $this->app['queue.failer']->setQueue($queue);
    }
}
