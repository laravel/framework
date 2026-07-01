<?php

namespace Illuminate\Contracts\Queue;

interface KeepsJobsAlive
{
    /**
     * Inform the queue driver that the given job is still being processed.
     *
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  int  $seconds
     * @return void
     */
    public function keepAlive(Job $job, int $seconds);
}
