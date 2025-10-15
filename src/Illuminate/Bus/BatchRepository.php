<?php

namespace Illuminate\Bus;

use Closure;

interface BatchRepository
{
    /**
     * Retrieve a list of batches.
     *
     * @param  int  $limit
     * @return \Illuminate\Bus\Batch[]
     */
    public function get($limit, $before);

    /**
     * Retrieve information about an existing batch.
     *
     * @return \Illuminate\Bus\Batch|null
     */
    public function find(string $batchId);

    /**
     * Store a new pending batch.
     *
     * @return \Illuminate\Bus\Batch
     */
    public function store(PendingBatch $batch);

    /**
     * Increment the total number of jobs within the batch.
     *
     * @return void
     */
    public function incrementTotalJobs(string $batchId, int $amount);

    /**
     * Decrement the total number of pending jobs for the batch.
     *
     * @return \Illuminate\Bus\UpdatedBatchJobCounts
     */
    public function decrementPendingJobs(string $batchId, string $jobId);

    /**
     * Increment the total number of failed jobs for the batch.
     *
     * @return \Illuminate\Bus\UpdatedBatchJobCounts
     */
    public function incrementFailedJobs(string $batchId, string $jobId);

    /**
     * Mark the batch that has the given ID as finished.
     *
     * @return void
     */
    public function markAsFinished(string $batchId);

    /**
     * Cancel the batch that has the given ID.
     *
     * @return void
     */
    public function cancel(string $batchId);

    /**
     * Delete the batch that has the given ID.
     *
     * @return void
     */
    public function delete(string $batchId);

    /**
     * Execute the given Closure within a storage specific transaction.
     */
    public function transaction(Closure $callback);

    /**
     * Rollback the last database transaction for the connection.
     *
     * @return void
     */
    public function rollBack();
}
