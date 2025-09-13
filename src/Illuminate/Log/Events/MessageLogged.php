<?php

namespace Illuminate\Log\Events;

class MessageLogged
{
    /**
     * Create a new event instance.
     *
     * @param  "emergency"|"alert"|"critical"|"error"|"warning"|"notice"|"info"|"debug"  $level
     * @param  string  $message
     * @param  array  $context
     */
    public function __construct(
        public $level,
        public $message,
        public array $context = [],
    ) {
    }
}
