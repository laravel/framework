<?php

namespace Illuminate\Http;

class EventStream
{
    /**
     * The name of the stream.
     *
     * @var string
     */
    public $event;

    /**
     * The data of the stream.
     *
     * @var mixed
     */
    public $data;

    /**
     * Create a new event stream instance.
     *
     * @param  string  $event
     * @param  mixed  $data
     * @return void
     */
    public function __construct($event, $data)
    {
        $this->event = $event;
        $this->data = $data;
    }
}
