<?php

namespace Illuminate\Console\Scheduling;

interface OverlappingStrategy
{
    /**
     * prevents overlapping for the given event
     *
     * @param Event $event
     * @return void
     */
    public function prevent(Event $event);

    /**
     * checks if the given event's command is already running
     *
     * @param Event $event
     * @return bool
     */
    public function overlaps(Event $event);

    /**
     * resets the overlapping strategy for the given event
     *
     * @param Event $event
     * @return void
     */
    public function reset(Event $event);
}
