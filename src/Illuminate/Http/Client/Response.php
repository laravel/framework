<?php

namespace Illuminate\Http\Client;

use Illuminate\Support\Traits\Macroable;

class Response
{
    use Macroable {
        __call as macroCall;
    }

    /**
     * The underlying PSR response.
     *
     * @var \Psr\Http\Message\MessageInterface
     */
    protected $response;

    /**
     * Create a new response instance.
     *
     * @param  \Psr\Http\Message\MessageInterface
     * @return void
     */
    public function __construct($response)
    {
        $this->response = $response;
    }

    /**
     * Get the body of the response.
     *
     * @return string
     */
    public function body()
    {
        return (string) $this->response->getBody();
    }

    /**
     * Get the JSON decoded body of the response.
     *
     * @return array
     */
    public function json()
    {
        return json_decode($this->response->getBody(), true);
    }

    /**
     * Get a header from the response.
     *
     * @return string
     */
    public function header(string $header)
    {
        return $this->response->getHeaderLine($header);
    }

    /**
     * Get the headers from the response.
     *
     * @return array
     */
    public function headers()
    {
        return collect($this->response->getHeaders())->mapWithKeys(function ($v, $k) {
            return [$k => $v];
        })->all();
    }

    /**
     * Get the status code of the response.
     *
     * @return int
     */
    public function status()
    {
        return $this->response->getStatusCode();
    }

    /**
     * Get the effective URI of the response.
     *
     * @return \Psr\Http\Message\UriInterface
     */
    public function effectiveUri()
    {
        return $this->transferStats->getEffectiveUri();
    }

    /**
     * Determine if the request was successful.
     *
     * @return bool
     */
    public function successful()
    {
        return $this->status() >= 200 && $this->status() < 300;
    }

    /**
     * Determine if the response code was "OK".
     *
     * @return bool
     */
    public function ok()
    {
        return $this->successful();
    }

    /**
     * Determine if the response was a redirect.
     *
     * @return bool
     */
    public function redirect()
    {
        return $this->status() >= 300 && $this->status() < 400;
    }

    /**
     * Detemine if the response indicates a client error occurred.
     *
     * @return bool
     */
    public function clientError()
    {
        return $this->status() >= 400 && $this->status() < 500;
    }

    /**
     * Detemine if the response indicates a server error occurred.
     *
     * @return bool
     */
    public function serverError()
    {
        return $this->status() >= 500;
    }

    /**
     * Get the response cookies.
     *
     * @return array
     */
    public function cookies()
    {
        return $this->cookies;
    }

    /**
     * Get the body of the response.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->body();
    }

    /**
     * Dynamically proxy other methods to the underlying response.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return static::hasMacro($method)
                    ? $this->macroCall($method, $parameters)
                    : $this->response->{$method}(...$parameters);
    }
}
