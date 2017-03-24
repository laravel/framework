<?php

namespace Illuminate\Console\Scheduling;

interface Mutex
{
    /**
     * Attempt to obtain a mutex for the given event.
     *
     * @param  \Illuminate\Console\Scheduling\Event  $event
     * @return bool
     */
    public function create(Event $event);

    /**
     * Determine if a mutex exists for the given event.
     *
     * @param  \Illuminate\Console\Scheduling\Event  $event
     * @return bool
     */
    public function exists(Event $event);

    /**
     * Clear the mutex for the given event.
     *
     * @param  \Illuminate\Console\Scheduling\Event  $event
     * @return void
     */
    public function forget(Event $event);
}
