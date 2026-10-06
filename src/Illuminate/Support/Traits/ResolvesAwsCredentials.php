<?php

namespace Illuminate\Support\Traits;

use Aws\Credentials\CredentialProvider;
use Illuminate\Support\Arr;
use Illuminate\Support\AwsCredentialCache;
use InvalidArgumentException;

trait ResolvesAwsCredentials
{
    /**
     * Configure the credentials for the given AWS client config.
     *
     * @param  array  $config
     * @return array
     *
     * @throws \InvalidArgumentException
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
                $this->cachedCredentialProvider(CredentialProvider::defaultProvider(), null, $config)
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
            $resolved = $this->cachedCredentialProvider($resolved, $provider, $config);
        }

        return CredentialProvider::memoize($resolved);
    }

    /**
     * Wrap the given credential provider so the credentials it resolves are shared across processes via the cache.
     *
     * @param  callable  $provider
     * @param  string|null  $name
     * @param  array  $config
     * @return callable
     */
    protected function cachedCredentialProvider(callable $provider, $name, array $config)
    {
        [$store, $fallbackStore] = [
            $config['credential_cache']['store'] ?? null,
            $config['credential_cache']['fallback_store'] ?? null,
        ];

        $cache = new AwsCredentialCache(
            fn () => $this->awsCredentialCacheRepository($store),
            $fallbackStore ? fn () => $this->awsCredentialCacheRepository($fallbackStore) : null,
        );

        $key = $this->awsCredentialCacheKey($name, $config);

        return fn () => $cache->resolve($key, $provider);
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

    /**
     * Get the cache repository for the given store name.
     *
     * @param  string|null  $store
     * @return \Illuminate\Contracts\Cache\Repository
     */
    abstract protected function awsCredentialCacheRepository($store);

    /**
     * Get the cache key for credentials resolved by the given provider.
     *
     * @param  string|null  $provider
     * @param  array  $config
     * @return string
     */
    abstract protected function awsCredentialCacheKey($provider, array $config);
}
