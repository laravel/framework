<?php

namespace Illuminate\Bus;

use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Throwable;

class ChainedBatch implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable;

    public Collection $jobs;

    public array $options;

    public string $name;

    public function __construct(PendingBatch $batch)
    {
        $this->jobs = static::prepareNestedBatches($batch->jobs);
        $this->options = $batch->options;
        $this->name = $batch->name;
    }

    public function handle(Container $container)
    {
        $batch = new PendingBatch($container, $this->jobs);
        $batch->name = $this->name;
        $batch->options = $this->options;

        $this->moveChainToEndOfBatch($batch);

        if ($this->queue) {
            $batch->onQueue($this->queue);
        }

        if ($this->connection) {
            $batch->onConnection($this->connection);
        }

        foreach ($this->chainCatchCallbacks ?? [] as $cb) {
            $batch->catch(function (Batch $batch, ?Throwable $exception) use ($cb) {
                if ($batch->allowsFailures()) {
                    return;
                }

                $cb($exception);
            });
        }

        $batch->dispatch();
    }

    protected function moveChainToEndOfBatch(PendingBatch $batch)
    {
        if (! empty($this->chained)) {
            $next = unserialize(array_shift($this->chained));
            $next->chained = $this->chained;

            $next->onConnection($next->connection ?: $this->chainConnection);
            $next->onQueue($next->queue ?: $this->chainQueue);

            $next->chainConnection = $this->chainConnection;
            $next->chainQueue = $this->chainQueue;
            $next->chainCatchCallbacks = $this->chainCatchCallbacks;

            $batch->finally(function (Batch $batch) use ($next) {
                if ($batch->canceled()) {
                    return;
                }

                dispatch($next);
            });

            $this->chained = [];
        }
    }

    public static function prepareNestedBatches(Collection $jobs): Collection
    {
        foreach ($jobs as $k => $job) {
            if (is_array($job)) {
                $jobs[$k] = static::prepareNestedBatches(collect($job))->all();
            }
            if ($job instanceof Collection) {
                $jobs[$k] = static::prepareNestedBatches($job);
            }

            if ($job instanceof PendingBatch) {
                $jobs[$k] = new ChainedBatch($job);
            }
        }

        return $jobs;
    }
}
