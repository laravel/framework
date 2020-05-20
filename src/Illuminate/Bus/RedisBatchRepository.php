<?php

namespace Illuminate\Bus;

use Closure;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Illuminate\Redis\Connections\Connection;

class RedisBatchRepository implements BatchRepository
{
    /**
     * The batch factory instance.
     *
     * @var \Illuminate\Bus\BatchFactory
     */
    protected $factory;

    /**
     * The Redis connection instance.
     *
     * @var \Illuminate\Contracts\Redis\Factory
     */
    public $redis;

    /**
     * The hash to use to store batch information.
     *
     * @var string
     */
    public $hashKey;

    /**
     * Create a new batch repository instance.
     *
     * @param  \Illuminate\Bus\BatchFactory  $factory
     * @param  \Illuminate\Redis\Connections\Connection  $connection
     * @param  string  $hashKey
     */
    public function __construct(BatchFactory $factory, Connection $connection, $hashKey)
    {
        $this->factory = $factory;
        $this->connection = $connection;
        $this->hashKey = $hashKey;
    }

    /**
     * Retrieve information about an existing batch.
     *
     * @param  string  $batchId
     * @return \Illuminate\Bus\Batch|null
     */
    public function find(string $batchId)
    {
        $batch = json_decode($this->connection->hget($this->hashKey, $batchId));

        if (! $batch) {
            return;
        }

        return $this->factory->make(
            $this,
            $batch->id,
            (int) $batch->totalJobs,
            (int) $batch->pendingJobs,
            (int) $batch->failedJobs,
            json_decode($batch->failedJobIds ?? "{}", true),
            isset($batch->options) ? unserialize($batch->options) : [],
            CarbonImmutable::createFromDate($batch->createdAt),
            $batch->cancelledAt ? CarbonImmutable::createFromDate($batch->cancelledAt) : $batch->cancelledAt,
            $batch->finishedAt ? CarbonImmutable::createFromDate($batch->finishedAt) : $batch->finishedAt
        );
    }

    /**
     * Store a new pending batch.
     *
     * @param  \Illuminate\Bus\PendingBatch  $batch
     * @return \Illuminate\Bus\Batch
     */
    public function store(PendingBatch $batch)
    {
        $id = (string) Str::orderedUuid();

        $this->connection->hset($this->hashKey,$id, json_encode([
            'id' => $id,
            'totalJobs' => 0,
            'pendingJobs' => 0,
            'failedJobs' => 0,
            'failedJobIds' => '[]',
            'options' => serialize($batch->options),
            'createdAt' => new CarbonImmutable(),
            'cancelledAt' => null,
            'finishedAt' => null,
        ]));

        return $this->find($id);
    }

    /**
     * Increment the total number of jobs within the batch.
     *
     * @param  string  $batchId
     * @param  int  $amount
     * @return void
     */
    public function incrementTotalJobs(string $batchId, int $amount)
    {
        $this->update($batchId,function($batch) use ($amount) {
            $batch->totalJobs += $amount;
            $batch->pendingJobs += $amount;
            $batch->finishedAt = null;
        });
    }

    /**
     * Decrement the total number of pending jobs for the batch.
     *
     * @param  string  $batchId
     * @param  string  $jobId
     * @return \Illuminate\Bus\UpdatedBatchJobCounts
     */
    public function decrementPendingJobs(string $batchId, string $jobId)
    {
        $batch = $this->update($batchId,function($batch) use ($jobId) {
            $batch->pendingJobs -= 1;
            $batch->failedJobs = $batch->failedJobs;
            $batch->failedJobIds = json_encode(array_values(array_diff(($batch->failedJobIds ?? []), [$jobId])));
        });

        return new UpdatedBatchJobCounts(
            $batch->pendingJobs,
            $batch->failedJobs
        );
    }

    /**
     * Increment the total number of failed jobs for the batch.
     *
     * @param  string  $batchId
     * @param  string  $jobId
     * @return \Illuminate\Bus\UpdatedBatchJobCounts
     */
    public function incrementFailedJobs(string $batchId, string $jobId)
    {
        $batch = $this->update($batchId,function($batch) use ($jobId) {
            $batch->pendingJobs = $batch->pendingJobs;
            $batch->failedJobs = $batch->failedJobs + 1;
            $batch->failedJobIds = json_encode(array_values(array_unique(array_merge(($batch->failedJobIds ?? []), [$jobId]))));
        });

        return new UpdatedBatchJobCounts(
            $batch->pendingJobs,
            $batch->failedJobs
        );
    }

    /**
     * Mark the batch that has the given ID as finished.
     *
     * @param  string  $batchId
     * @return void
     */
    public function markAsFinished(string $batchId)
    {
        $this->update($batchId, function($batch){
            $batch->finishedAt = new CarbonImmutable();
        });
    }

    /**
     * Cancel the batch that has the given ID.
     *
     * @param  string  $batchId
     * @return void
     */
    public function cancel(string $batchId)
    {
        $this->update($batchId, function($batch){
            $batch->cancelled_at = new CarbonImmutable();
            $batch->finishedAt = new CarbonImmutable();
        });
    }

    /**
     * Delete the batch that has the given ID.
     *
     * @param  string  $batchId
     * @return void
     */
    public function delete(string $batchId)
    {
        $this->connection->hdel($this->hashKey, $batchId);
    }

    /**
     * Execute the given Closure within a storage specific transaction.
     *
     * @param  \Closure  $callback
     * @return mixed
     */
    public function transaction(Closure $callback)
    {
        return $callback($this);
    }

    /**
     * Update batch information through a given clousre.
     *
     * @param  \Closure  $callback
     * @return mixed
     */
    protected function update($batchId, Closure $callback) {
        $batch = $this->find($batchId);

        $callback($batch);

        $this->connection->hset($this->hashKey, $batchId, json_encode($batch));

        return $batch;
    }
}
