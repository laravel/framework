<?php

namespace Illuminate\Http;

class EventStream
{
    /**
     * The name of the stream.
     *
     * @var string
     */
    public $as;

    /**
     * The data to stream.
     */
    public $data;

    /**
     * Create a new event stream instance.
     *
     * @param  string  $as
     * @param  mixed  $data
     * @return void
     */
    public function __construct($as, $data)
    {
        $this->as = $as;
        $this->data = $data;
    }
}
