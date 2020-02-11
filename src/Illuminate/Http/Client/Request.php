<?php

namespace Illuminate\Http\Client;

class Request
{
    /**
     * Create a new request instance.
     *
     * @param  \GuzzleHttp\Psr7\RequestInterface  $request
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
