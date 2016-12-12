<?php

namespace Illuminate\Redis;

use Closure;
use Predis\Client;
use Illuminate\Support\Arr;

class PredisDatabase extends Database
{
    /**
     * The host address of the database.
     *
     * @var array
     */
    public $clients;

    /**
     * Create a new Redis connection instance.
     *
     * @param  array  $servers
     * @return void
     */
    public function __construct(array $servers = [])
    {
        $clusters = (array) Arr::pull($servers, 'clusters');

        $options = array_merge(['timeout' => 10.0], (array) Arr::pull($servers, 'options'));

        $this->clients = $this->createSingleClients($servers, $options);

        $this->createClusters($clusters, $options);
    }

    /**
     * Create an array of single connection clients.
     *
     * @param  array  $servers
     * @param  array  $options
     * @return array
     */
    protected function createSingleClients(array $servers, array $options = [])
    {
        $clients = [];

        foreach ($servers as $key => $server) {
            $clients[$key] = new Client($server, $options);
        }

        return $clients;
    }

    /**
     * Create multiple clusters (aggregate clients).
     *
     * @param  array  $clusters
     * @param  array  $options
     * @return void
     */
    protected function createClusters(array $clusters, array $options = [])
    {
        $options = array_merge($options, (array) Arr::pull($clusters, 'options'));

        foreach ($clusters as $name => $servers) {
            $this->clients += $this->createAggregateClient($name, $servers, array_merge(
                $options, (array) Arr::pull($servers, 'options')
            ));
        }
    }

    /**
     * Create a new aggregate client supporting sharding.
     *
     * @param  string  $name
     * @param  array  $servers
     * @param  array  $options
     * @return array
     */
    protected function createAggregateClient($name, array $servers, array $options = [])
    {
        return [$name => new Client(array_values($servers), $options)];
    }

    /**
     * Subscribe to a set of given channels for messages.
     *
     * @param  array|string  $channels
     * @param  \Closure  $callback
     * @param  string  $connection
     * @param  string  $method
     * @return void
     */
    public function subscribe($channels, Closure $callback, $connection = null, $method = 'subscribe')
    {
        $loop = $this->connection($connection)->pubSubLoop();

        call_user_func_array([$loop, $method], (array) $channels);

        foreach ($loop as $message) {
            if ($message->kind === 'message' || $message->kind === 'pmessage') {
                call_user_func($callback, $message->payload, $message->channel);
            }
        }

        unset($loop);
    }

    /**
     * Subscribe to a set of given channels with wildcards.
     *
     * @param  array|string  $channels
     * @param  \Closure  $callback
     * @param  string  $connection
     * @return void
     */
    public function psubscribe($channels, Closure $callback, $connection = null)
    {
        $this->subscribe($channels, $callback, $connection, __FUNCTION__);
    }
}
