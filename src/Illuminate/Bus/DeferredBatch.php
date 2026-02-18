<?php

namespace Illuminate\Bus;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use InvalidArgumentException;
use Laravel\SerializableClosure\SerializableClosure;
use Throwable;

class DeferredBatch implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The batch builder callback.
     *
     * @var \Laravel\SerializableClosure\SerializableClosure|callable
     */
    public $builder;

    /**
     * Create a new deferred batch instance.
     *
     * The builder callable receives no arguments and must return
     * a PendingBatch instance, or null to skip and continue the chain.
     *
     * @param  callable  $builder
     */
    public function __construct(callable $builder)
    {
        $this->builder = $builder instanceof Closure
            ? new SerializableClosure($builder)
            : $builder;
    }

    /**
     * Handle the job.
     *
     * @return void
     */
    public function handle()
    {
        $batch = call_user_func($this->builder);

        if ($batch === null) {
            return;
        }

        if (! $batch instanceof PendingBatch) {
            throw new InvalidArgumentException(
                'DeferredBatch builder must return a PendingBatch or null.'
            );
        }

        foreach ($this->chainCatchCallbacks ?? [] as $callback) {
            $batch->catch(function (Batch $batchInstance, ?Throwable $e) use ($callback) {
                if (! $batchInstance->allowsFailures()) {
                    $callback($e);
                }
            });
        }

        $this->attachRemainderOfChainToEndOfBatch($batch)->dispatch();
    }

    /**
     * Move the remainder of the chain to a "finally" batch callback.
     *
     * @param  \Illuminate\Bus\PendingBatch  $batch
     * @return \Illuminate\Bus\PendingBatch
     */
    protected function attachRemainderOfChainToEndOfBatch(PendingBatch $batch)
    {
        if (is_array($this->chained) && ! empty($this->chained)) {
            $next = unserialize(array_shift($this->chained));

            $next->chained = $this->chained;

            $next->onConnection($next->connection ?: $this->chainConnection);
            $next->onQueue($next->queue ?: $this->chainQueue);

            $next->chainConnection = $this->chainConnection;
            $next->chainQueue = $this->chainQueue;
            $next->chainCatchCallbacks = $this->chainCatchCallbacks;

            $batch->finally(function (Batch $batch) use ($next) {
                if (! $batch->cancelled()) {
                    Container::getInstance()->make(Dispatcher::class)->dispatch($next);
                }
            });

            $this->chained = [];
        }

        return $batch;
    }
}
