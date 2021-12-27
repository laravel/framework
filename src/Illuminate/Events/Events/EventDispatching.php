<?php

namespace Illuminate\Events\Events;

class EventDispatching
{
    public $event;

    public $payload;

    public function __construct($event, array $payload)
    {
        $this->event = $event;
        $this->payload = $payload;
    }
}
