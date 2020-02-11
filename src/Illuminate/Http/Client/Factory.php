<?php

namespace Illuminate\Http\Client;

class Factory
{
    /**
     * The stub callables that will handle requests.
     *
     * @var \Illuminate\Support\Collection|null
     */
    protected $expectations;

    /**
     * Create a new response instance.
     *
     * @param  string  $body
     * @param  int  $status
     * @param  array  $headers
     * @return \GuzzleHttp\Psr7\Response
     */
    public function response($body = null, $status = 200, $headers = [])
    {
        return \GuzzleHttp\Promise\promise_for(new \GuzzleHttp\Psr7\Response($status, $headers, $body));
    }

    /**
     * Register a stub callable that will intercept requests and be able to return stub responses.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function stub($callback)
    {
        $this->expectations = collect($callback);

        return $this;
    }

    /**
     * Execute a method against a new pending request instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return tap(new PendingRequest, function ($request) {
            $request->stub($this->expectations);
        })->{$method}(...$parameters);
    }
}
