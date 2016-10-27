<?php

namespace Illuminate\Broadcasting\Broadcasters;

use ReflectionFunction;
use ReflectionParameter;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Contracts\Broadcasting\Broadcaster as BroadcasterContract;

abstract class Broadcaster implements BroadcasterContract
{
    /**
     * The registered channel authenticators.
     *
     * @var array
     */
    protected $channels = [];

    /**
     * Register a channel authenticator.
     *
     * @param  string  $channel
     * @param  callable  $callback
     * @return $this
     */
    public function channel($channel, callable $callback)
    {
        $this->channels[$channel] = $callback;

        return $this;
    }

    /**
     * Authenticate the incoming request for a given channel.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $channel
     * @return mixed
     */
    protected function verifyUserCanAccessChannel($request, $channel)
    {
        foreach ($this->channels as $pattern => $callback) {
            if (! Str::is(preg_replace('/\{(.*?)\}/', '*', $pattern), $channel)) {
                continue;
            }

            $parameters = $this->extractAuthParameters($pattern, $channel, $callback);

            if ($result = $callback($request->user(), ...$parameters)) {
                return $this->validAuthenticationResponse($request, $result);
            }
        }

        throw new HttpException(403);
    }

    /**
     * Extract the parameters from the given pattern and channel.
     *
     * @param  string  $pattern
     * @param  string  $channel
     * @param  callable  $callback
     * @return array
     */
    protected function extractAuthParameters($pattern, $channel, $callback)
    {
        $parameters = [];

        $pattern = preg_replace('/\{(.*?)\}/', '([^\.]+)', $pattern);

        preg_match('/^'.$pattern.'/', $channel, $keys);

        $callbackParameters = (new ReflectionFunction($callback))->getParameters();

        foreach ($callbackParameters as $parameter) {
            if ($parameter->getPosition() === 0) {
                continue;
            }

            $parameters[] = ! isset($keys[$parameter->getPosition()])
                            ? null : $this->getAuthParameterFromKeys($parameter, $keys);
        }

        return $parameters;
    }

    /**
     * Format the channel array into an array of strings.
     *
     * @param  array  $channels
     * @return array
     */
    protected function formatChannels(array $channels)
    {
        return array_map(function ($channel) {
            return (string) $channel;
        }, $channels);
    }

    /**
     * Extract a parameter from the given keys.
     *
     * @param  ReflectionParameter  $parameter
     * @param  array  $keys
     * @return mixed
     */
    protected function getAuthParameterFromKeys($parameter, $keys)
    {
        $key = $keys[$parameter->getPosition()];

        if ($parameter->getClass() && $parameter->getClass()->isSubclassOf(Model::class)) {
            $model = $parameter->getClass()->newInstance();

            return $model->where($model->getRouteKeyName(), $key)->first();
        }

        return $key;
    }
}
