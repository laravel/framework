<?php

namespace Illuminate\Queue;

use Illuminate\Queue\SerializableClosure;
use Illuminate\Support\Str;

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
        return $this->data->size;
    }

    /**
     * The number of pending jobs.
     *
     * @return int
     */
    public function pending()
    {
        return app('cache')->get('batch_'.$this->id.'_counter');
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
        return app('cache')->get('batch_'.$this->id.'_failed');
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

        return $pending < $this->data->size && $pending > 0;
    }

    /**
     * Determine if the batch has failed.
     *
     * @return bool
     */
    public function hasFailed()
    {
        return (bool) app('cache')->get('batch_'.$id.'_fail');
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
        if (app('cache')->decrement('batch_'.$this->id.'_counter') == 0
            && $this->data->success) {
            app()->call(unserialize($this->data->success)->getClosure());
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
        app('cache')->put('batch_'.$id.'_fail', 1, 3600);

        app()->call(unserialize($this->data->failure)->getClosure());
    }

    /**
     * Delete the batch from storage.
     *
     * @return void
     */
    public function delete()
    {
        app('cache')->forget('batch_'.$this->id.'_counter');
        app('cache')->forget('batch_'.$this->id);
    }
}
