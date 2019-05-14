<?php

namespace Illuminate\Redis\Connections;

use Closure;
use Predis\Command\ServerFlushDatabase;
use Predis\Connection\Aggregate\PredisCluster;
use Illuminate\Contracts\Redis\Connection as ConnectionContract;

/**
 * @mixin \Predis\Client
 */
class PredisConnection extends Connection implements ConnectionContract
{
    /**
     * Create a new Predis connection.
     *
     * @param  \Predis\Client  $client
     * @return void
     */
    public function __construct($client)
    {
        $this->client = $client;
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
        $loop = $this->pubSubLoop();

        call_user_func_array([$loop, $method], (array) $channels);

        foreach ($loop as $message) {
            if ($message->kind === 'message' || $message->kind === 'pmessage') {
                call_user_func($callback, $message->payload, $message->channel);
            }
        }

        unset($loop);
    }

    /**
     * Flush the selected Redis database.
     *
     * @return void
     */
    public function flushdb()
    {
        if (! $this->client->getConnection() instanceof PredisCluster) {
            return $this->command('flushdb');
        }

        foreach ($this->getConnection() as $node) {
            $node->executeCommand(new ServerFlushDatabase);
        }
    }
}
