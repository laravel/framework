<?php

namespace Illuminate\Http\Client;

use ArrayAccess;
use LogicException;

class Request implements ArrayAccess
{
    /**
     * The underlying PSR request.
     *
     * @var \Psr\Http\Message\RequestInterface
     */
    protected $request;

    /**
     * The decoded JSON request.
     *
     * @var array
     */
    protected $decoded;

    /**
     * Create a new request instance.
     *
     * @param  \Psr\Http\Message\RequestInterface  $request
     * @return void
     */
    public function __construct($request)
    {
        $this->request = $request;
    }

    /**
     * Get the request method.
     *
     * @return strign
     */
    public function method()
    {
        return $this->request->getMethod();
    }

    /**
     * Get the URL of the request.
     *
     * @return string
     */
    public function url()
    {
        return (string) $this->request->getUri();
    }

    /**
     * Get the body of the request.
     *
     * @return string
     */
    public function body()
    {
        return (string) $this->request->getBody();
    }

    /**
     * Get the JSON decoded body of the request.
     *
     * @return array
     */
    public function json()
    {
        if (! $this->decoded) {
            $this->decoded = json_decode((string) $this->request->getBody(), true);
        }

        return $this->decoded;
    }

    /**
     * Determine if the given offset exists.
     *
     * @param  string  $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->json()[$offset]);
    }

    /**
     * Get the value for a given offset.
     *
     * @param  string  $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->json()[$offset];
    }

    /**
     * Set the value at the given offset.
     *
     * @param  string  $offset
     * @param  mixed  $value
     * @return void
     *
     * @throws \LogicException
     */
    public function offsetSet($offset, $value)
    {
        throw new LogicException('Request data may not be mutated using array access.');
    }

    /**
     * Unset the value at the given offset.
     *
     * @param  string  $offset
     * @return void
     *
     * @throws \LogicException
     */
    public function offsetUnset($offset)
    {
        throw new LogicException('Request data may not be mutated using array access.');
    }

    /**
     * Get the request headers.
     *
     * @return array
     */
    public function headers()
    {
        return collect($this->request->getHeaders())->mapWithKeys(function ($values, $header) {
            return [$header => $values];
        })->all();
    }
}
