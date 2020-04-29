<?php

namespace Illuminate\Queue;

class ExistingBatch
{
    /**
     * The id of the batch.
     *
     * @var string
     */
    public $id;

    /**
     * The batch data.
     *
     * @var object
     */
    public $data;

    /**
     * Create a new batch instance.
     *
     * @param  string  $id
     * @return void
     */
    public function __construct($id)
    {
        if (! $data = app('cache')->get('batch_'.$id)) {
            throw new \InvalidArgumentException('Batch not found!');
        }

        $this->id = $id;
        $this->data = json_decode($data);
    }

    /**
     * The size of the batch.
     *
     * @return int
     */
    public function size()
    {
        return (int) app('cache')->get('batch_'.$this->id.'_size');
    }

    /**
     * The number of pending jobs.
     *
     * @return int
     */
    public function pending()
    {
        return (int) app('cache')->get('batch_'.$this->id.'_pending');
    }

    /**
     * The number of processed jobs.
     *
     * @return int
     */
    public function processed()
    {
        return $this->size() - $this->pending();
    }

    /**
     * The number of failed jobs.
     *
     * @return int
     */
    public function failures()
    {
        return (int) app('cache')->get('batch_'.$this->id.'_failed');
    }

    /**
     * Determine if the batch allows failure.
     *
     * @return bool
     */
    public function allowsFailure()
    {
        return $this->data->allowFailure;
    }

    /**
     * Determine if the batch is running.
     *
     * @return bool
     */
    public function isRunning()
    {
        if ($this->hasFailed()) {
            return false;
        }

        $pending = $this->pending();

        return $pending < $this->size() && $pending > 0;
    }

    /**
     * Determine if the batch has failed.
     *
     * @return bool
     */
    public function hasFailed()
    {
        return (bool) app('cache')->get('batch_'.$this->id.'_fail');
    }

    /**
     * Determine if the batch has finished.
     *
     * @return bool
     */
    public function hasFinished()
    {
        return $this->hasFailed() || $this->pending() == 0;
    }

    /**
     * Count a job as processed.
     *
     * @return void
     */
    public function countJob()
    {
        if (! $this->hasFailed() &&
            app('cache')->decrement('batch_'.$this->id.'_pending') == 0 &&
            $this->data->success) {
            app()->call(unserialize($this->data->success)->getClosure(), [$this]);
        }
    }

    /**
     * Handle a failed job.
     *
     * @return void
     */
    public function failJob()
    {
        app('cache')->increment('batch_'.$this->id.'_failed');

        return $this->allowsFailure()
                    ? $this->countJob()
                    : $this->fail();
    }

    /**
     * Fail the entire batch.
     *
     * @return void
     */
    public function fail()
    {
        app('cache')->put('batch_'.$this->id.'_fail', 1, 3600);

        app()->call(unserialize($this->data->failure)->getClosure(), [$this]);
    }

    /**
     * Add more jobs to the batch.
     *
     * @param  array  $jobs
     * @return void
     */
    public function add($jobs)
    {
        app('cache')->increment('batch_'.$this->id.'_size', count($jobs));
        app('cache')->increment('batch_'.$this->id.'_pending', count($jobs));

        foreach ($jobs as $job) {
            $job->batchId($this->id);

            $job->onConnection($job->connection ?: $this->data->connection);
            $job->onQueue($job->queue ?: $this->data->queue);

            dispatch($job);
        }
    }

    /**
     * Delete the batch from storage.
     *
     * @return void
     */
    public function delete()
    {
        app('cache')->forget('batch_'.$this->id.'_failed');
        app('cache')->forget('batch_'.$this->id.'_fail');
        app('cache')->forget('batch_'.$this->id.'_pending');
        app('cache')->forget('batch_'.$this->id.'_size');
        app('cache')->forget('batch_'.$this->id);
    }
}
