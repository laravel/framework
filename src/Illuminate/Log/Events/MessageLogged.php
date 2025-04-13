<?php

namespace Illuminate\Log\Events;

class MessageLogged
{
    /**
     * The log "level".
     *
     * @var string
     */
    public $level;

    /**
     * The log message.
     *
     * @var string
     */
    public $message;

    /**
     * The log context.
     *
     * @var array
     */
    public $context;

    /**
     * Create a new event instance.
     *
     * @param  string  $level
     * @param  string  $message
     * @param  array  $context
     */
    public function __construct($level, $message, array $context = [])
    {
        $this->level = $level;
        $this->message = $message;
        $this->context = $context;
    }
}
