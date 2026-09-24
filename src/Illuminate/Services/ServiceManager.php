<?php

namespace Illuminate\Services;

use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Str;
use LogicException;
use ReflectionClass;

class ServiceManager
{
    /**
     * Create a new service manager instance.
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     */
    public function __construct(protected Container $container)
    {
        //
    }

    /**
     * Get a client for calling the given service without a job class.
     *
     * @param  string  $name
     * @return \Illuminate\Services\ServiceClient
     */
    public function service(string $name)
    {
        return new ServiceClient($this, $name);
    }

    /**
     * Get an HTTP request configured for the given service.
     *
     * @param  string  $name
     * @return \Illuminate\Http\Client\PendingRequest
     *
     * @throws \LogicException
     */
    public function http(string $name)
    {
        $config = $this->config($name);

        return $this->container->make(HttpFactory::class)
            ->baseUrl($config['url'])
            ->acceptJson()
            ->when($config['token'] ?? null, fn ($request, $token) => $request->withToken($token));
    }

    /**
     * Fake the responses of remote jobs and services.
     *
     * Keys may be a remote job class or a "service/path" pattern.
     *
     * @param  array<string, mixed>  $responses
     * @return $this
     */
    public function fake(array $responses = [])
    {
        $http = $this->container->make(HttpFactory::class);

        $stubs = [];

        foreach ($responses as $target => $response) {
            $stubs[$this->urlFor($target)] = is_array($response) ? $http->response($response) : $response;
        }

        $http->fake($stubs);

        return $this;
    }

    /**
     * Assert that a remote job or service path was called.
     *
     * @param  string  $target
     * @param  (callable(array, \Illuminate\Http\Client\Request): bool)|null  $callback
     * @return void
     */
    public function assertCalled(string $target, ?callable $callback = null)
    {
        $url = $this->urlFor($target);

        $this->container->make(HttpFactory::class)->assertSent(
            fn (Request $request) => Str::is($url, $request->url())
                && ($callback === null || $callback($request->data(), $request))
        );
    }

    /**
     * Get the URL pattern for a remote job class or a "service/path" pattern.
     *
     * @param  string  $target
     * @return string
     */
    protected function urlFor(string $target)
    {
        if (class_exists($target)) {
            $job = (new ReflectionClass($target))->newInstanceWithoutConstructor();

            return rtrim($this->config($job->remoteService())['url'], '/').'/'.$job->remoteName();
        }

        [$service, $path] = array_pad(explode('/', $target, 2), 2, '*');

        return rtrim($this->config($service)['url'], '/').'/'.$path;
    }

    /**
     * Get the configuration for the given service.
     *
     * @param  string  $name
     * @return array
     *
     * @throws \LogicException
     */
    protected function config(string $name)
    {
        $config = $this->container->make('config')->get("services.{$name}", []);

        if (! is_string($config['url'] ?? null) || $config['url'] === '') {
            throw new LogicException("Service [{$name}] does not have a URL configured.");
        }

        return $config;
    }

    /**
     * Get a client for the service with the given name.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return \Illuminate\Services\ServiceClient
     */
    public function __call($method, $parameters)
    {
        return $this->service($method);
    }
}
