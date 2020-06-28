<?php


namespace Illuminate\Http\Client\Event;


use Illuminate\Http\Client\Response;

class PendingRequestSent
{

    /**
     * The pending request method.
     *
     * @var string
     */
    public $method;


    /**
     * The Http client Response.
     *
     * @var Response|null
     */
    public $response;
    /**
     * @var string
     */
    public $url;
    /**
     * @var array
     */
    public $options;


    /**
     * Create a new event instance.
     *
     * @param string $method
     * @param string $url
     * @param array $options
     * @param \Illuminate\Http\Client\Response $response
     * @param array $data
     */
    public function __construct($method, $url, $options, $response = null)
    {
        $this->method = $method;
        $this->response = $response;
        $this->url = $url;
        $this->options = $options;
    }

}

