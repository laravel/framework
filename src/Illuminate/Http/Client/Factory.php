<?php

namespace Illuminate\Http\Client;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Support\Str;

class Factory
{
    /**
     * The stub callables that will handle requests.
     *
     * @var \Illuminate\Support\Collection|null
     */
    protected $expectations;

    /**
     * Create a new factory instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->expectations = collect();
    }

    /**
     * Create a new response instance.
     *
     * @param  string  $body
     * @param  int  $status
     * @param  array  $headers
     * @return \GuzzleHttp\Promise\PromiseInterface
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
        $this->expectations = $this->expectations->merge(collect($callback));

        return $this;
    }

    /**
     * Stub the given URL using the given callback.
     *
     * @param  string  $url
     * @param  \Illuminate\Http\Client\Response|\GuzzleHttp\Promise\PromiseInterface|callable  $callback
     * @return $this
     */
    public function stubUrl($url, $callback)
    {
        return $this->stub(function ($request, $options) use ($url, $callback) {
            if (Str::is($url, $request->url())) {
                return $callback instanceof PromiseInterface || $callback instanceof Response
                                ? $callback
                                : $callback($request, $options);
            }
        });
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
