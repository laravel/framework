<?php

namespace Illuminate\Queue\Connectors;

use Illuminate\Support\Arr;
use Illuminate\Queue\ElasticsearchQueue;

class ElasticsearchConnector implements ConnectorInterface
{
    /**
     * Establish a queue connection.
     *
     * @param  array  $config
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public function connect(array $config)
    {
        $client = \Elasticsearch\ClientBuilder::create()->setHosts($config['host'])->build();

        return new ElasticsearchQueue(
            $client,
            $config['index'],
            $config['queue'],
            Arr::get($config, 'retry_after', 60)
        );
    }
}
