<?php

namespace Illuminate\Queue\Connectors;

use Aws\Credentials\CredentialProvider;
use Aws\Sqs\SqsClient;
use Illuminate\Container\Container;
use Illuminate\Queue\AwsCredentialCache;
use Illuminate\Queue\SqsQueue;
use Illuminate\Support\Arr;
use InvalidArgumentException;

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
     * Configure the credentials for the given connection config.
     *
     * @param  array  $config
     * @return array
     */
    protected function withCredentials(array $config)
    {
        if ($credentials = $this->resolveCredentialProvider($config)) {
            $config['credentials'] = $credentials;
        } elseif (! empty($config['key']) && ! empty($config['secret'])) {
            $config['credentials'] = Arr::only($config, ['key', 'secret']);

            if (! empty($config['token'])) {
                $config['credentials']['token'] = $config['token'];
            }
        } elseif (! array_key_exists('credentials', $config) && $this->credentialCachingEnabled($config)) {
            $config['credentials'] = CredentialProvider::memoize(
                $this->cachedCredentialProvider(CredentialProvider::defaultProvider(), $config)
            );
        }

        return $config;
    }

    /**
     * Resolve a credential provider from the given config.
     *
     * @param  array  $config
     * @return callable|null
     *
     * @throws \InvalidArgumentException
     */
    protected function resolveCredentialProvider(array $config)
    {
        $credentials = $config['credentials'] ?? null;

        $provider = is_array($credentials) ? ($credentials['provider'] ?? null) : $credentials;

        if (! is_string($provider)) {
            return $provider;
        }

        $options = is_array($credentials) ? Arr::except($credentials, ['provider']) : [];

        $resolved = match ($provider) {
            'ecs' => CredentialProvider::ecsCredentials($options),
            'instance' => CredentialProvider::instanceProfile($options),
            default => throw new InvalidArgumentException(
                "Invalid credential provider [{$provider}]."
            ),
        };

        if ($this->credentialCachingEnabled($config)) {
            $resolved = $this->cachedCredentialProvider($resolved, $config);
        }

        return CredentialProvider::memoize($resolved);
    }

    /**
     * Wrap the given credential provider so the credentials it resolves are shared across processes via the cache.
     *
     * @param  callable  $provider
     * @param  array  $config
     * @return callable
     */
    protected function cachedCredentialProvider(callable $provider, array $config)
    {
        [$store, $fallbackStore] = [
            $config['credential_cache']['store'] ?? null,
            $config['credential_cache']['fallback_store'] ?? null,
        ];

        $cache = new AwsCredentialCache(
            fn () => Container::getInstance()->make('cache')->store($store),
            $fallbackStore ? fn () => Container::getInstance()->make('cache')->store($fallbackStore) : null,
        );

        return fn () => $cache->resolve(static::credentialsCacheKey($config), $provider);
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

        return 'aws:sqs:credentials:'.hash('sha256', implode('|', [
            $_ENV['AWS_CONTAINER_CREDENTIALS_FULL_URI'] ?? $_SERVER['AWS_CONTAINER_CREDENTIALS_FULL_URI'] ?? '',
            $_ENV['AWS_CONTAINER_AUTHORIZATION_TOKEN_FILE'] ?? $_SERVER['AWS_CONTAINER_AUTHORIZATION_TOKEN_FILE'] ?? '',
            is_string($provider) ? $provider : '',
            $config['region'] ?? '',
            $config['prefix'] ?? '',
            $config['suffix'] ?? '',
        ]));
    }

    /**
     * Determine if resolved credentials should be shared across processes via the cache.
     *
     * @param  array  $config
     * @return bool
     */
    protected function credentialCachingEnabled(array $config)
    {
        return (bool) ($config['credential_cache']['enabled'] ?? false);
    }
}
