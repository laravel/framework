<?php

namespace Illuminate\Bus;

use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatabaseBatchRepository implements BatchRepository
{
    /**
     * The batch factory instance.
     *
     * @var \Illuminate\Bus\BatchFactory
     */
    protected $factory;

    /**
     * The database connection instance.
     *
     * @var \Illuminate\Database\Connection
     */
    protected $connection;

    /**
     * The database table to use to store batch information.
     *
     * @var string
     */
    protected $table;

    /**
     * Create a new batch repository instance.
     *
     * @param  \Illuminate\Bus\BatchFactory  $factory
     * @param  \Illuminate\Database\Connection  $connection
     * @param  string  $table
     */
    public function __construct(BatchFactory $factory, Connection $connection, string $table)
    {
        $this->factory = $factory;
        $this->connection = $connection;
        $this->table = $table;
    }

    /**
     * Retrieve information about an existing batch.
     *
     * @param  string  $batchId
     * @return \Illuminate\Bus\Batch
     */
    public function find(string $batchId)
    {
        $batch = $this->connection->table($this->table)
                            ->where('id', $batchId)
                            ->first();

        if (! $batch) {
            return null;
        }

        return $this->factory->make(
            $this,
            $batch->id,
            (int) $batch->total_jobs,
            (int) $batch->pending_jobs,
            (int) $batch->failed_jobs,
            unserialize($batch->options),
            CarbonImmutable::createFromTimestamp($batch->created_at),
            $batch->cancelled_at ? CarbonImmutable::createFromTimestamp($batch->cancelled_at) : $batch->cancelled_at,
            $batch->finished_at ? CarbonImmutable::createFromTimestamp($batch->finished_at) : $batch->finished_at
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

        $this->connection->table($this->table)->insert([
            'id' => $id,
            'total_jobs' => 0,
            'pending_jobs' => 0,
            'failed_jobs' => 0,
            'options' => serialize($batch->options),
            'created_at' => time(),
            'cancelled_at' => null,
            'finished_at' => null,
        ]);

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
        $this->connection->table($this->table)->where('id', $batchId)->update([
            'total_jobs' => DB::raw('total_jobs + '.$amount),
            'pending_jobs' => DB::raw('pending_jobs + '.$amount),
            'finished_at' => null,
        ]);
    }

    /**
     * Decrement the total number of pending jobs for the batch.
     *
     * @param  string  $batchId
     * @return int|null
     */
    public function decrementPendingJobs(string $batchId)
    {
        return $this->updateAtomicValue($batchId, function ($batch) {
            return ['pending_jobs' => $batch->pending_jobs - 1];
        });
    }

    /**
     * Increment the total number of failed jobs for the batch.
     *
     * @param  string  $batchId
     * @return int|null
     */
    public function incrementFailedJobs(string $batchId)
    {
        return $this->updateAtomicValue($batchId, function ($batch) {
            return ['failed_jobs' => $batch->failed_jobs + 1];
        });
    }

    /**
     * Update an atomic value within the batch.
     *
     * @param  string  $batchId
     * @param  \Closure  $callback
     * @return int|null
     */
    protected function updateAtomicValue(string $batchId, Closure $callback)
    {
        return $this->connection->transaction(function () use ($batchId, $callback) {
            $batch = $this->connection->table($this->table)->where('id', $batchId)
                        ->lockForUpdate()
                        ->first();

            return is_null($batch) ? null : head(tap($callback($batch), function ($value) use ($batchId) {
                $this->connection->table($this->table)->where('id', $batchId)->update([
                    head(array_flip($value)) => head($value),
                ]);
            }));
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
        $this->connection->table($this->table)->where('id', $batchId)->update([
            'cancelled_at' => time(),
            'finished_at' => time(),
        ]);
    }

    /**
     * Delete the batch that has the given ID.
     *
     * @param  string  $batchId
     * @return void
     */
    public function delete(string $batchId)
    {
        $this->connection->table($this->table)->where('id', $batchId)->delete();
    }

    /**
     * Execute the given Closure within a storage specific transaction.
     *
     * @param  \Closure  $callback
     * @return mixed
     */
    public function transaction(Closure $callback)
    {
        return $this->connection->transaction(function () use ($callback) {
            return $callback();
        });
    }
}
