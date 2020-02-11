<?php

namespace Illuminate\Http\Client;

use Closure;
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
     * Create a new response instance for use during stubbing.
     *
     * @param  array|string  $body
     * @param  int  $status
     * @param  array  $headers
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public static function response($body = null, $status = 200, $headers = [])
    {
        if (is_array($body)) {
            $body = json_encode($body);

            $headers['Content-Type'] = 'application/json';
        }

        return \GuzzleHttp\Promise\promise_for(new \GuzzleHttp\Psr7\Response($status, $headers, $body));
    }

    /**
     * Get an invokable object that returns a sequence of responses in order for use during stubbing.
     *
     * @param  array  $responses
     * @return \Illuminate\Http\Client\ResponseSequence
     */
    public static function sequence(array $responses)
    {
        return new ResponseSequence($responses);
    }

    /**
     * Register a stub callable that will intercept requests and be able to return stub responses.
     *
     * @param  callable|array  $callback
     * @return $this
     */
    public function stub($callback)
    {
        if (is_array($callback)) {
            foreach ($callback as $url => $callable) {
                $this->stubUrl($url, $callable);
            }
        } else {
            $this->expectations = $this->expectations->merge(collect([$callback]));
        }

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
            if (Str::is(Str::start($url, '*'), $request->url())) {
                return $callback instanceof Closure || $callback instanceof ResponseSequence
                            ? $callback($request, $options)
                            : $callback;
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
