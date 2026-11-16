<?php

namespace Illuminate\Queue\Connectors;

use Aws\Sqs\SqsClient;
use Illuminate\Container\Container;
use Illuminate\Queue\SqsQueue;
use Illuminate\Support\Aws\ClientFactory;

class SqsConnector implements ConnectorInterface
{
    /**
     * Establish a queue connection.
     *
     * @param  array  $config
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public function connect(array $config)
    {
        $client = Container::getInstance()->make(ClientFactory::class)->make(
            SqsClient::class, $config, 'queue.connections.'.($config['name'] ?? 'ondemand'), [
                'http' => ['timeout' => 60, 'connect_timeout' => 60],
            ],
        );

        return new SqsQueue(
            $client,
            $config['queue'],
            $config['prefix'] ?? '',
            $config['suffix'] ?? '',
            $config['after_commit'] ?? null,
            $config['overflow'] ?? [],
        );
    }
}
