<?php

namespace Illuminate\Http\Exceptions;

use RuntimeException;

class HttpRedirectException extends RuntimeException
{
    /**
     * The URL to redirect to.
     *
     * @var string
     */
    protected $uri;

    /**
     * The status code to use for the redirect.
     *
     * @var int
     */
    protected $statusCode;

    /**
     * The headers to send with the redirect.
     *
     * @var array
     */
    protected $headers = [];

    /**
     * Whether the redirect should be securedone.
     *
     * @var bool|null
     */
    protected $secure;

    /**
     * Create a new HTTP redirect exception instance.
     *
     * @param  string  $uri
     * @param  int  $statusCode
     * @param  array $headers
     * @param  bool|null  $secure
     * @return void
     */
    public function __construct($uri, $statusCode = 302, array $headers = [], $secure = null)
    {
        $this->uri = $uri;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        $this->secure = $secure;
    }

    /**
     * Get the uri.
     *
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * Get the status code.
     *
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Get the headers.
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Get the secure flag.
     *
     * @return bool|null
     */
    public function getSecure()
    {
        return $this->secure;
    }
}
