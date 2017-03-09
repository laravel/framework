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
     * Release the job back into the queue.
     *
     * @param  int   $delay
     * @return mixed|void
     */
    public function release($delay = 0);

    /**
     * Delete the job from the queue.
     *
     * @return void
     */
    public function delete();

    /**
     * Determine if the job has been deleted.
     *
     * @return bool
     */
    public function isDeleted();

    /**
     * Determine if the job has been deleted or released.
     *
     * @return bool
     */
    public function isDeletedOrReleased();

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts();

    /**
     * Process an exception that caused the job to fail.
     *
     * @param  \Throwable  $e
     * @return void
     */
    public function failed($e);

    /**
     * The number of times to attempt a job.
     *
     * @return int|null
     */
    public function maxTries();

    /**
     * The number of seconds the job can run.
     *
     * @return int|null
     */
    public function timeout();

    /**
     * Get the name of the queued job class.
     *
     * @return string
     */
    public function getName();

    /**
     * Get the resolved name of the queued job class.
     *
     * Resolves the name of "wrapped" jobs such as class-based handlers.
     *
     * @return string
     */
    public function resolveName();

    /**
     * Get the name of the connection the job belongs to.
     *
     * @return string
     */
    public function getConnectionName();

    /**
     * Get the name of the queue the job belongs to.
     *
     * @return string
     */
    public function getQueue();

     /**
      * Get the raw body string for the job.
      *
      * @return string
      */
     public function getRawBody();
}
