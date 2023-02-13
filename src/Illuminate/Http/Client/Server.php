<?php

namespace Illuminate\Http\Client;

use Illuminate\Support\Traits\ForwardsCalls;

/**
 * @mixin \Illuminate\Http\Client\PendingRequest
 */
abstract class Server
{
    use ForwardsCalls;

    /**
     * The quick actions for this server.
     *
     * @var array{string,string}
     */
    protected $actions = [];

    /**
     * The base URL of this server.
     *
     * @var string
     */
    protected $baseUrl = '';

    /**
     * The headers to include in the server requests.
     *
     * @var array
     */
    protected $headers = [];

    /**
     * The bearer token to use as authentication mechanism.
     *
     * @var string
     */
    protected $authToken;

    /**
     * The request to be sent, once built.
     *
     * @var \Illuminate\Http\Client\PendingRequest
     */
    protected $request;

    /**
     * Create a new Server instance.
     *
     * @param  \Illuminate\Http\Client\Factory  $factory
     * @param  array  $urlParameters
     */
    public function __construct(protected $factory, protected $urlParameters = [])
    {
        //
    }

    /**
     * Customize the request created for this server.
     *
     * @param  \Illuminate\Http\Client\PendingRequest  $request
     * @return \Illuminate\Http\Client\PendingRequest|void
     */
    protected function build($request)
    {
        //
    }

    /**
     * Sets the query parameters for the URI.
     *
     * @param  array  $parameters
     * @return $this
     */
    public function parameters(array $parameters)
    {
        $this->urlParameters = $parameters;

        return $this;
    }

    /**
     * Builds a request based on the server configuration.
     *
     * @return \Illuminate\Http\Client\PendingRequest
     */
    protected function request()
    {
        return $this->request ??= tap(
            $this->factory
                ->baseUrl($this->baseUrl)
                ->when($this->authToken)->withToken($this->authToken)
                ->when($this->headers)->withHeaders($this->headers)
                ->when($this->urlParameters)->withUrlParameters($this->urlParameters),
            $this->build(...)
        );
    }

    /**
     * Redirects the wait procedure to the underlying request promise.
     *
     * @param  bool  $unwrap
     * @return mixed
     */
    public function wait($unwrap = true)
    {
        return $this->getPromise()->wait($unwrap);
    }

    /**
     * Dynamically handle calls to the underlying request.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return $this|object
     *
     * @throws \Exception
     */
    public function __call(string $method, array $parameters)
    {
        if (isset($this->actions[$method])) {
            [$verb, $path] = str_contains($this->actions[$method], ':')
                ? explode(':', $this->actions[$method], 2)
                : ['get', $this->actions[$method]];

            return $this->request()->{$verb}($path, ...$parameters);
        }

        return $this->forwardDecoratedCallTo($this->request(), $method, $parameters);
    }
}
