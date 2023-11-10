<?php

namespace Illuminate\Queue;

use DateTimeInterface;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Support\InteractsWithTime;
use InvalidArgumentException;
use Throwable;

trait InteractsWithQueue
{
    use InteractsWithTime;

    /**
     * The underlying queue job instance.
     *
     * @var \Illuminate\Contracts\Queue\Job|null
     */
    public $job;

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts()
    {
        return $this->job ? $this->job->attempts() : 1;
    }

    /**
     * Delete the job from the queue.
     *
     * @return void
     */
    public function delete()
    {
        if ($this->job) {
            return $this->job->delete();
        }
    }

    /**
     * Fail the job from the queue.
     *
     * @param  \Throwable|string|null  $exception
     * @return void
     */
    public function fail($exception = null)
    {
        if (is_string($exception)) {
            $exception = new ManuallyFailedException($exception);
        }

        if ($exception instanceof Throwable || is_null($exception)) {
            if ($this->job) {
                return $this->job->fail($exception);
            }
        } else {
            throw new InvalidArgumentException('The fail method requires a string or an instance of Throwable.');
        }
    }

    /**
     * Release the job back into the queue after (n) seconds.
     *
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @return void
     */
    public function release($delay = 0)
    {
        $delay = $delay instanceof DateTimeInterface
            ? $this->secondsUntil($delay)
            : $delay;

        if ($this->job) {
            return $this->job->release($delay);
        }
    }

    /**
     * Set the base queue job instance.
     *
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @return $this
     */
    public function setJob(JobContract $job)
    {
        $this->job = $job;

        return $this;
    }
}
