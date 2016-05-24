<?php

namespace Illuminate\Broadcasting\Broadcasters;

use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

abstract class AbstractBroadcaster
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * The registered channel authenticators.
     *
     * @var array
     */
    protected $channels = [];

    /**
     * Create a new broadcaster instance.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Register a channel authenticator.
     *
     * @param  string  $channel
     * @param  callable  $callback
     * @return $this
     */
    public function auth($channel, $callback)
    {
        $this->channels[$channel] = $callback;

        return $this;
    }

    /**
     * Authenticate the incoming request for a given channel.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function check($request)
    {
        $channel = str_replace(['private-', 'presence-'], '', $request->channel_name);

        foreach ($this->channels as $pattern => $callback) {
            if (! Str::is($pattern, $channel)) {
                continue;
            }

            $parameters = $this->extractAuthParameters($pattern, $channel);

            $resolvedCallback = $this->makeAuthenticator($callback);

            if ($result = $resolvedCallback($request->user(), ...$parameters)) {
                return $this->validAuthenticationResponse($request, $result);
            }
        }

        throw new HttpException(403);
    }

    /**
     * Register an authenticator.
     *
     * @param  mixed  $callback
     * @return callable
     */
    public function makeAuthenticator($callback)
    {
        return is_string($callback) ? $this->createClassAuthenticator($callback) : $callback;
    }

    /**
     * Create a class based authenticator using the IoC container.
     *
     * @param  mixed  $callback
     * @return \Closure
     */
    public function createClassAuthenticator($callback)
    {
        $container = $this->app;

        return function () use ($callback, $container) {
            return call_user_func_array(
                $this->createClassCallable($callback, $container), func_get_args()
            );
        };
    }

    /**
     * Create the class based authenticator callable.
     *
     * @param  string  $callback
     * @param  \Illuminate\Container\Container  $container
     * @return callable
     */
    protected function createClassCallable($callback, $container)
    {
        list($class, $method) = $this->parseClassCallable($callback);

        return [$container->make($class), $method];
    }

    /**
     * Parse the class authenticator into class and method.
     *
     * @param  string  $callback
     * @return array
     */
    protected function parseClassCallable($callback)
    {
        $segments = explode('@', $callback);

        return [$segments[0], count($segments) == 2 ? $segments[1] : 'authenticate'];
    }

    /**
     * Return the valid authentication response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $result
     * @return mixed
     */
    public function validAuthenticationResponse($request, $result)
    {
        return false;
    }

    /**
     * Extract the parameters from the given pattern and channel.
     *
     * @param  string  $pattern
     * @param  string  $channel
     * @return array
     */
    protected function extractAuthParameters($pattern, $channel)
    {
        if (! Str::contains($pattern, '*')) {
            return [];
        }

        $pattern = str_replace('\*', '([^\.]+)', preg_quote($pattern));

        if (preg_match('/^'.$pattern.'/', $channel, $keys)) {
            array_shift($keys);

            return $keys;
        }

        return [];
    }

    /**
     * Return a response to requests to save socket.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function rememberSocket($request)
    {
        return false;
    }
}
