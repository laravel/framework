<?php

namespace Illuminate\Queue\Connectors;

use Aws\Sqs\SqsClient;
use Illuminate\Queue\SqsQueue;
use Illuminate\Support\Arr;

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
        $config = $this->getDefaultConfiguration($config);

        if (! empty($config['key']) && ! empty($config['secret'])) {
            $config['credentials'] = Arr::only($config, ['key', 'secret']);
            if (! empty($config['token'])) {
                $config['credentials']['token'] = $config['token'];
            }
        }

        if (empty($config['endpoint']) && ! empty($config['prefix']) && filter_var($config['prefix'], FILTER_VALIDATE_URL)) {
            $parts = parse_url($config['prefix']);

            if ($parts !== false && isset($parts['scheme'], $parts['host'])) {
                $endpoint = $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
                $config['endpoint'] = $endpoint;
            }
        }

        return new SqsQueue(
            new SqsClient(
                Arr::except($config, ['token'])
            ),
            $config['queue'],
            $config['prefix'] ?? '',
            $config['suffix'] ?? '',
            $config['after_commit'] ?? null
        );
    }

    /**
     * Get the default configuration for SQS.
     *
     * @param  array  $config
     * @return array
     */
    protected function getDefaultConfiguration(array $config)
    {
        return array_merge([
            'version' => 'latest',
            'http' => [
                'timeout' => 60,
                'connect_timeout' => 60,
            ],
        ], $config);
    }
}
