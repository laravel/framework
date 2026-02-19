<?php

namespace Illuminate\Bus;

use Illuminate\Container\Container;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;

class ChainedBatch implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable;

    /**
     * The collection of batched jobs.
     *
     * @var \Illuminate\Support\Collection
     */
    public Collection $jobs;

    /**
     * The name of the batch.
     *
     * @var string
     */
    public string $name;

    /**
     * The batch options.
     *
     * @var array
     */
    public array $options;

    /**
     * Create a new chained batch instance.
     *
     * @param  \Illuminate\Bus\PendingBatch  $batch
     */
    public function __construct(PendingBatch $batch)
    {
        $this->jobs = static::prepareNestedBatches($batch->jobs);

        $this->name = $batch->name;
        $this->options = $batch->options;

        $this->queue = $batch->queue();
        $this->connection = $batch->connection();
    }

    /**
     * Prepare any nested batches within the given collection of jobs.
     *
     * @param  \Illuminate\Support\Collection  $jobs
     * @return \Illuminate\Support\Collection
     */
    public static function prepareNestedBatches(Collection $jobs): Collection
    {
        return $jobs->filter()->values()->map(fn ($job) => match (true) {
            is_array($job) => static::prepareNestedBatches(new Collection($job))->all(),
            $job instanceof Collection => static::prepareNestedBatches($job),
            $job instanceof PendingBatch => new ChainedBatch($job),
            default => $job,
        });
    }

    /**
     * Handle the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->dispatchBatchInChain($this->toPendingBatch());
    }

    /**
     * Convert the chained batch instance into a pending batch.
     *
     * @return \Illuminate\Bus\PendingBatch
     */
    public function toPendingBatch()
    {
        $batch = Container::getInstance()->make(Dispatcher::class)->batch($this->jobs);

        $batch->name = $this->name;
        $batch->options = $this->options;

        if ($this->queue) {
            $batch->onQueue($this->queue);
        }

        if ($this->connection) {
            $batch->onConnection($this->connection);
        }

        return $batch;
    }
}
