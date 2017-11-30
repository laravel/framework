<?php

namespace Illuminate\Log;

use Illuminate\Contracts\Events\Dispatcher;

class EventLogger
{
    /**
     * The event dispatcher.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * The event that we are logging, a string or class event.
     *
     * @var string
     */
    protected $event;

    /**
     * The level at which our event should be logged.
     *
     * @var string
     */
    protected $level;

    /**
     * The log manager instance.
     *
     * @var \Illuminate\Log\LogManager
     */
    protected $logs;

    /**
     * Create the EventLogger.
     *
     * @param  \Illuminate\Log\LogManager  $logs
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @param  string  $event
     * @param  string  $level
     * @return void
     */
    public function __construct(LogManager $logs, Dispatcher $events, $event, $level = 'debug')
    {
        $this->events = $events;
        $this->event = $event;
        $this->level = $level;
        $this->logs = $logs;
    }

    /**
     * Set the channels to which the given event should be logged.
     *
     * @param  mixed  $channels
     * @return void
     */
    public function to($channels)
    {
        $channels = is_array($channels) ? $channels : func_get_args();

        foreach ($channels as $channel) {
            $this->logs->to($channel)->listen($this->events, $this->event, $this->level);
        }
    }
}
