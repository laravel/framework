<?php

namespace Illuminate\Contracts\Queue;

interface Job
{
    /**
     * Fire the job.
     *
     * @return void
     */
    public function fire();

    /**
     * Delete the job from the queue.
     *
     * @return void
     */
    public function delete();

    /**
     * Release the job back into the queue.
     *
     * @param  int   $delay
     * @return void
     */
    public function release($delay = 0);

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts();

    /**
     * Get the name of the queued job class.
     *
     * @return string
     */
    public function getName();

    /**
     * Get the name of the queue the job belongs to.
     *
     * @return string
     */
    public function getQueue();
}
