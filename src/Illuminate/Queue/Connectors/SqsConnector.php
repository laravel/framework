<?php

namespace Illuminate\Queue\Connectors;

use Aws\Sqs\SqsClient;
use Illuminate\Container\Container;
use Illuminate\Queue\SqsQueue;
use Illuminate\Support\Arr;
use Illuminate\Support\AwsCredentialCache;
use Illuminate\Support\Traits\ResolvesAwsCredentials;

class SqsConnector implements ConnectorInterface
{
    use ResolvesAwsCredentials;

    /**
     * Establish a queue connection.
     *
     * @param  array  $config
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public function connect(array $config)
    {
        $config = $this->withCredentials(
            $this->getDefaultConfiguration($config)
        );

        return new SqsQueue(
            new SqsClient(
                Arr::except($config, ['token', 'overflow', 'credential_cache'])
            ),
            $config['queue'],
            $config['prefix'] ?? '',
            $config['suffix'] ?? '',
            $config['after_commit'] ?? null,
            $config['overflow'] ?? [],
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

    /**
     * Get the cache repository for the given store name.
     *
     * @param  string|null  $store
     * @return \Illuminate\Contracts\Cache\Repository
     */
    protected function awsCredentialCacheRepository($store)
    {
        return Container::getInstance()->make('cache')->store($store);
    }

    /**
     * Get the cache key for credentials resolved by the given provider.
     *
     * @param  string|null  $provider
     * @param  array  $config
     * @return string
     */
    protected function awsCredentialCacheKey($provider, array $config)
    {
        return static::credentialsCacheKey($config);
    }

    /**
     * Get the cache key for the connection's shared credentials.
     *
     * @param  array  $config
     * @return string
     */
    public static function credentialsCacheKey(array $config)
    {
        $credentials = $config['credentials'] ?? null;

        $provider = is_array($credentials) ? ($credentials['provider'] ?? null) : $credentials;

        return AwsCredentialCache::key('sqs', is_string($provider) ? $provider : null, [
            $config['region'] ?? '',
            $config['prefix'] ?? '',
            $config['suffix'] ?? '',
        ]);
    }
}
