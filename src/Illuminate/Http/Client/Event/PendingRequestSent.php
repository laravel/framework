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
     *
     * @var Response
     */
    public $data;
    /**
     * The Http client Response.
     *
     * @var Response|null
     */
    protected $response;
    /**
     * @var string
     */
    protected $url;
    /**
     * @var array
     */
    protected $options;


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

