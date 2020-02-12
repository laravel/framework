<?php

namespace Illuminate\Http\Client;

use ArrayAccess;
use Illuminate\Support\Str;
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
     * The decoded payload for the request.
     *
     * @var array
     */
    protected $data;

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
     * Determine if the request has a given header.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return bool
     */
    public function hasHeader($key, $value = null)
    {
        return is_null($value)
                    ? ! empty($this->request->getHeaders()[$key])
                    : in_array($value, $this->headers()[$key]);
    }

    /**
     * Get the values for the header with the given name.
     *
     * @return array
     */
    public function header($key)
    {
        return $this->headers()[$key];
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
     * Get the request's data (form parameters or JSON).
     *
     * @return array
     */
    public function data()
    {
        if ($this->hasHeader('Content-Type', 'application/x-www-form-urlencoded')) {
            return $this->parameters();
        } elseif (Str::contains($this->header('Content-Type')[0], 'json')) {
            return $this->json();
        } else {
            return $this->data ?? [];
        }
    }

    /**
     * Get the request's form parameters.
     *
     * @return array
     */
    protected function parameters()
    {
        if (! $this->data) {
            parse_str($this->body(), $parameters);

            $this->data = $parameters;
        }

        return $this->data;
    }

    /**
     * Get the JSON decoded body of the request.
     *
     * @return array
     */
    protected function json()
    {
        if (! $this->data) {
            $this->data = json_decode($this->body(), true);
        }

        return $this->data;
    }

    /**
     * Set the decoded data on the request.
     *
     * @param  array  $data
     * @return $this
     */
    public function withData(array $data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Determine if the given offset exists.
     *
     * @param  string  $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->data()[$offset]);
    }

    /**
     * Get the value for a given offset.
     *
     * @param  string  $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->data()[$offset];
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
}
