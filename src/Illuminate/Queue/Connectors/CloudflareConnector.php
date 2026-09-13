<?php

namespace Illuminate\Queue\Connectors;

use Illuminate\Http\Client\Factory;
use Illuminate\Queue\CloudflareQueue;
use Illuminate\Queue\CloudflareQueueClient;
use InvalidArgumentException;

class CloudflareConnector implements ConnectorInterface
{
    /**
     * Create a new Cloudflare connector instance.
     */
    public function __construct(protected Factory $http)
    {
        //
    }

    /**
     * Establish a queue connection.
     *
     * @param  array  $config
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public function connect(array $config)
    {
        foreach (['account_id', 'queue_id'] as $required) {
            if (empty($config[$required])) {
                throw new InvalidArgumentException(
                    "Cloudflare Queue config is missing required key: [{$required}]."
                );
            }
        }

        if (empty($config['token'] ?? null) && empty($config['api_token'] ?? null)) {
            throw new InvalidArgumentException(
                'Cloudflare Queue config is missing required key: [token] or [api_token].'
            );
        }

        $config['token'] ??= $config['api_token'];

        return new CloudflareQueue(
            new CloudflareQueueClient($this->http, $config),
            $config['queue'] ?? 'default',
            $config['after_commit'] ?? false,
            (int) ($config['batch_size'] ?? 1),
            (int) ($config['visibility_timeout_ms'] ?? 30_000),
        );
    }
}
