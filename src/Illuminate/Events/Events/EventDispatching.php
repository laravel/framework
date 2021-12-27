<?php

namespace Illuminate\Events\Events;

class EventDispatching
{
    public $event;

    public $payload;

    public $halt;

    public function __construct($event, array $payload, bool $halt)
    {
        $this->event = $event;
        $this->payload = $payload;
        $this->halt = $halt;
    }
}
