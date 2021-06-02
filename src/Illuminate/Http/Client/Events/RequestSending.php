<?php

namespace Illuminate\Http\Client\Events;

class RequestSending
{
    /**
     * The HTTP method used to send the request.
     *
     * @var string
     */
    public $method;

    /**
     * The URL that the request was sent to.
     *
     * @var string
     */
    public $url;

    /**
     * The options that were sent along with the request.
     *
     * @var array
     */
    public $options;

    /**
     * Create a new event instance.
     *
     * @param  string  $method
     * @param  string  $url
     * @param  array  $options
     * @return void
     */
    public function __construct(string $method, string $url, array $options)
    {
        $this->method = $method;
        $this->url = $url;
        $this->options = $options;
    }
}
