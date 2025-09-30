<?php

namespace Illuminate\Redis\Connections;

use Closure;
use Illuminate\Contracts\Redis\Connection as ConnectionContract;
use Illuminate\Support\Collection;
use Predis\Command\Argument\ArrayableArgument;

/**
 * @mixin \Predis\Client
 */
class PredisConnection extends Connection implements ConnectionContract
{
    /**
     * The Predis client.
     *
     * @var \Predis\Client
     */
    protected $client;

    /**
     * Create a new Predis connection.
     *
     * @param  \Predis\Client  $client
     */
    public function __construct($client)
    {
        $this->client = $client;
    }

    /**
     * Build the list of channels, applying the prefix except for skipped patterns.
     *
     * @param  array<int, string>  $channels
     * @return array<int, string>
     */
    protected function channelsWithAppliedPrefix(array $channels): array
    {
        $patterns = ($this->config['options']['skip_prefix_for_channels'] ?? null) ?: [
            '/^__keyevent@\d+__:/',
            '/^__keyspace@\d+__:/',
        ];

        $options = $this->client->getOptions();
        $prefix = '';
        if (isset($options->prefix)) {
            $prefix = is_object($options->prefix) && method_exists($options->prefix, 'getPrefix')
                ? $options->prefix->getPrefix()
                : (string) $options->prefix;
        }

        return array_map(function ($channel) use ($patterns, $prefix) {
            foreach ($patterns as $regex) {
                if (@preg_match($regex, $channel) === 1) {
                    return $channel; // skip prefix
                }
            }
            return $prefix ? $prefix.$channel : $channel;
        }, $channels);
    }


    /**
     * Subscribe to a set of given channels for messages.
     *
     * @param  array|string  $channels
     * @param  \Closure  $callback
     * @param  string  $method
     * @return void
     */
    public function createSubscription($channels, Closure $callback, $method = 'subscribe')
    {
        $channels = (array) $channels;
        $channels = $this->channelsWithAppliedPrefix($channels);

        $loop = $this->pubSubLoop();
        $loop->{$method}(...array_values($channels));

        foreach ($loop as $message) {
            if ($message->kind === 'message' || $message->kind === 'pmessage') {
                $callback($message->payload, $message->channel);
            }
        }

        unset($loop);
    }

    /**
     * Parse the command's parameters for event dispatching.
     *
     * @param  array  $parameters
     * @return array
     */
    protected function parseParametersForEvent(array $parameters)
    {
        return (new Collection($parameters))
            ->transform(function ($parameter) {
                return $parameter instanceof ArrayableArgument
                    ? $parameter->toArray()
                    : $parameter;
            })->all();
    }
}
