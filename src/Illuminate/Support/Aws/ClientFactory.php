<?php

namespace Illuminate\Support\Aws;

use Aws\Credentials\CredentialProvider;
use Aws\Credentials\EcsCredentialProvider;
use Aws\Handler\HttpTransportSharing;
use Closure;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\Container as ContainerContract;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use RuntimeException;
use UnexpectedValueException;

#[Singleton]
class ClientFactory
{
    /**
     * The options read by the SDK's default credential provider chain.
     *
     * @var list<string>
     */
    protected const CREDENTIAL_OPTIONS = [
        'client',
        'disableAssumeRole',
        'ec2_metadata_service_endpoint',
        'ec2_metadata_service_endpoint_mode',
        'ec2_metadata_v1_disabled',
        'filename',
        'preferStaticCredentials',
        'region',
        'retries',
        'retryable_error_codes',
        'retryable_exceptions',
        'RoleArn',
        'SessionName',
        'source',
        'ssoClient',
        'ssoOidcClient',
        'stsClient',
        'timeout',
        'use_aws_shared_config_files',
        'WebIdentityTokenFile',
    ];

    /**
     * The options to apply to every client.
     *
     * @var \Closure|array
     */
    protected $globalOptions = [];

    /**
     * The custom client resolvers, keyed by client class or configuration context.
     *
     * @var array<string, \Closure>
     */
    protected $resolvers = [];

    /**
     * The credential providers shared between clients with the same credential source.
     *
     * @var array<string, array{provider: callable, fingerprint: string, source: array}>
     */
    protected $credentialProviders = [];

    /**
     * Create a new AWS client factory instance.
     *
     * @param  \Illuminate\Contracts\Container\Container|null  $container
     */
    public function __construct(protected ?ContainerContract $container = null)
    {
        //
    }

    /**
     * Create a new AWS SDK client instance.
     *
     * @template TClient of \Aws\AwsClient
     *
     * @param  class-string<TClient>  $client
     * @param  array  $config
     * @param  string  $context
     * @param  array  $defaults
     * @return TClient
     *
     * @throws \RuntimeException
     * @throws \UnexpectedValueException
     */
    public function make(string $client, array $config, string $context, array $defaults = [])
    {
        if (! class_exists($client)) {
            throw new RuntimeException(
                "The [aws/aws-sdk-php] package is required to use [{$context}]. You may install it via: composer require aws/aws-sdk-php"
            );
        }

        $config = $this->withTransportSharing($this->withCredentials(
            $this->mergeOptions($client, $config, $context, $defaults), $context
        ));

        // The SDK ignores options it doesn't know, so the configuration is passed as it is. But the
        // "token" option is removed, since the SDK would read the session token as a bearer token
        // for the client, and the credential cache options are only meant for this factory...
        $config = Arr::except($config, ['token', 'credential_cache']);

        if (! $resolver = $this->resolvers[$context] ?? $this->resolvers[$client] ?? null) {
            return new $client($config);
        }

        $instance = $resolver($config, $this);

        if (! $instance instanceof $client) {
            throw new UnexpectedValueException(
                "The client resolver for [{$context}] must return an instance of [{$client}]."
            );
        }

        return $instance;
    }

    /**
     * Set the options to apply to every client.
     *
     * @param  (\Closure(string, array, string): array)|array  $options
     * @return $this
     */
    public function globalOptions($options)
    {
        $this->globalOptions = $options;

        return $this;
    }

    /**
     * Register a callback that creates the client for the given client class or configuration context.
     *
     * @param  string  $clientOrContext
     * @param  \Closure(array, \Illuminate\Support\Aws\ClientFactory): \Aws\AwsClient  $callback
     * @return $this
     */
    public function resolveUsing(string $clientOrContext, Closure $callback)
    {
        $this->resolvers[$clientOrContext] = $callback;

        return $this;
    }

    /**
     * Get the key under which the credentials of the given client configuration are shared across processes.
     *
     * @param  class-string<\Aws\AwsClient>  $client
     * @param  array  $config
     * @param  string  $context
     * @return string|null
     */
    public function credentialsCacheKey(string $client, array $config, string $context)
    {
        $source = $this->credentialSource($this->mergeOptions($client, $config, $context, []));

        return $source && ! $this->containsObjects($source) ? $this->sourceCacheKey($source) : null;
    }

    /**
     * Merge the site defaults, the global options, and the given configuration.
     *
     * @param  class-string<\Aws\AwsClient>  $client
     * @param  array  $config
     * @param  string  $context
     * @param  array  $defaults
     * @return array
     */
    protected function mergeOptions(string $client, array $config, string $context, array $defaults)
    {
        $global = $this->globalOptions instanceof Closure
            ? ($this->globalOptions)($client, $config, $context)
            : $this->globalOptions;

        // Options without a value, like an unset environment variable, fall back to the
        // global options. And a connection that configures its own credentials should
        // not inherit any of the credential options that were set globally either...
        $config = array_filter($config, fn ($value) => ! is_null($value));

        if (isset($config['credentials']) ||
            isset($config['profile']) ||
            (! empty($config['key']) && ! empty($config['secret']))) {
            $global = Arr::except($global, ['credentials', 'profile', 'key', 'secret', 'token']);
        }

        $options = array_replace($defaults, $global, $config);

        // The connection's "http" options replace the site defaults as they always have,
        // but are merged with the global "http" options so both may be used together...
        $http = array_replace(
            isset($config['http']) ? [] : ($defaults['http'] ?? []),
            $global['http'] ?? [],
            $config['http'] ?? [],
        );

        if ($http !== []) {
            $options['http'] = $http;
        }

        return $options;
    }

    /**
     * Configure the credentials for the given client configuration.
     *
     * @param  array  $config
     * @param  string  $context
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function withCredentials(array $config, string $context)
    {
        if ($source = $this->credentialSource($config)) {
            $config['credentials'] = $this->sharedCredentialProvider($source, $config, $context);

            // The SDK would build the credentials from the profile again and ignore ours, so it's removed...
            unset($config['profile']);
        } elseif ($provider = $this->credentialProvider($config)) {
            $config['credentials'] = $provider;
        } elseif (! empty($config['key']) && ! empty($config['secret'])) {
            $config['credentials'] = Arr::only($config, ['key', 'secret']);

            if (! empty($config['token'])) {
                $config['credentials']['token'] = $config['token'];
            }
        }

        return $config;
    }

    /**
     * Get the credential source the factory resolves for the given client configuration.
     *
     * @param  array  $config
     * @return array{provider: string|null, profile?: string, options: array}|null
     */
    protected function credentialSource(array $config)
    {
        $credentials = $config['credentials'] ?? null;

        // Without a region, the SDK resolves one from the environment before it builds the profile or
        // the default chain, so we let the SDK build them itself. Unless the credentials should be
        // cached across processes, since every process would then fetch the credentials alone...
        $resolvable = ! empty($config['region']) || ($config['credential_cache']['enabled'] ?? false);

        // A profile always wins over any other credentials, just like it does within the SDK...
        if (isset($config['profile'])) {
            return $resolvable ? [
                'provider' => null,
                'profile' => $config['profile'],
                'options' => Arr::only($config, static::CREDENTIAL_OPTIONS),
            ] : null;
        }

        if (is_string($provider = $this->credentialProvider($config))) {
            return [
                'provider' => $provider,
                'options' => is_array($credentials) ? Arr::except($credentials, ['provider']) : [],
            ];
        }

        if (is_null($credentials) && $resolvable && (empty($config['key']) || empty($config['secret']))) {
            return ['provider' => null, 'options' => Arr::only($config, static::CREDENTIAL_OPTIONS)];
        }

        return null;
    }

    /**
     * Get the credential provider given in the client configuration, if any.
     *
     * @param  array  $config
     * @return mixed
     */
    protected function credentialProvider(array $config)
    {
        $credentials = $config['credentials'] ?? null;

        return is_array($credentials) ? ($credentials['provider'] ?? null) : $credentials;
    }

    /**
     * Get the memoized credential provider for the given credential source, creating it if needed.
     *
     * @param  array{provider: string|null, profile?: string, options: array}  $source
     * @param  array  $config
     * @param  string  $context
     * @return callable
     *
     * @throws \InvalidArgumentException
     */
    protected function sharedCredentialProvider(array $source, array $config, string $context)
    {
        $objects = $this->containsObjects($source);

        // Objects cannot be compared across processes, so a source containing any objects is never
        // cached across processes. Within the process, it's kept once per connection and replaced
        // when it changes, since objects can only be told apart while they are still alive...
        $cache = ! $objects && ($config['credential_cache']['enabled'] ?? false) ? $config['credential_cache'] : null;

        $fingerprint = hash('xxh128', serialize($this->normalize([$source, $cache], withIds: true)));

        $key = $objects ? "context:{$context}" : $fingerprint;

        if (($this->credentialProviders[$key]['fingerprint'] ?? null) === $fingerprint) {
            return $this->credentialProviders[$key]['provider'];
        }

        $provider = isset($source['profile'])
            ? CredentialProvider::ini($source['profile'], null, $source['options'])
            : match ($source['provider']) {
                null => CredentialProvider::defaultProvider($source['options']),
                'ecs' => CredentialProvider::ecsCredentials($source['options']),
                'instance' => CredentialProvider::instanceProfile($source['options']),
                default => throw new InvalidArgumentException("Invalid credential provider [{$source['provider']}]."),
            };

        if ($cache) {
            $provider = $this->cachedCredentialProvider($provider, $this->sourceCacheKey($source), $cache);
        }

        $this->credentialProviders[$key] = [
            'provider' => CredentialProvider::memoize($provider),
            'fingerprint' => $fingerprint,
            'source' => $source,
        ];

        return $this->credentialProviders[$key]['provider'];
    }

    /**
     * Get the key under which the credentials of the given credential source are shared across processes.
     *
     * @param  array{provider: string|null, profile?: string, options: array}  $source
     * @return string
     */
    protected function sourceCacheKey(array $source)
    {
        // The identity behind the credentials is also decided by the environment, so the variables
        // the SDK reads are part of the key, read the same way the SDK reads them. Workloads that
        // can't be told apart by their environment, like EKS Pod Identity, need their own prefix...
        $environment = array_map(fn ($name) => getenv($name) ?: ($_SERVER[$name] ?? ''), [
            EcsCredentialProvider::ENV_URI,
            EcsCredentialProvider::ENV_FULL_URI,
            EcsCredentialProvider::ENV_AUTH_TOKEN_FILE,
            CredentialProvider::ENV_ARN,
            CredentialProvider::ENV_TOKEN_FILE,
            CredentialProvider::ENV_PROFILE,
        ]);

        return 'aws:credentials:'.hash('sha256', serialize([$environment, $this->normalize($source, withIds: false)]));
    }

    /**
     * Wrap the given credential provider so the credentials it resolves are shared across processes via the cache.
     *
     * @param  callable  $provider
     * @param  string  $key
     * @param  array  $options
     * @return callable
     */
    protected function cachedCredentialProvider(callable $provider, string $key, array $options)
    {
        $cache = new CredentialCache(
            fn () => $this->cache()->store($options['store'] ?? null),
            ! empty($options['fallback_store']) ? fn () => $this->cache()->store($options['fallback_store']) : null,
        );

        $resolving = false;
        $direct = null;

        return function () use ($cache, $key, $provider, &$resolving, &$direct) {
            if ($resolving) {
                return $direct ??= $provider();
            }

            $resolving = true;

            try {
                return $cache->resolve($key, function () use ($provider, &$direct) {
                    return $direct ??= $provider();
                });
            } finally {
                $resolving = false;
                $direct = null;
            }
        };
    }

    /**
     * Get the cache manager instance.
     *
     * @return \Illuminate\Contracts\Cache\Factory
     */
    protected function cache()
    {
        return ($this->container ?? Container::getInstance())->make('cache');
    }

    /**
     * Normalize the given value so it can be serialized into a key.
     *
     * @param  mixed  $value
     * @param  bool  $withIds
     * @return mixed
     */
    protected function normalize($value, bool $withIds)
    {
        if (is_array($value)) {
            ksort($value);

            return array_map(fn ($item) => $this->normalize($item, $withIds), $value);
        }

        if (is_object($value)) {
            return get_class($value).($withIds ? '#'.spl_object_id($value) : '');
        }

        return $value;
    }

    /**
     * Determine if the given array contains any objects.
     *
     * @param  array  $value
     * @return bool
     */
    protected function containsObjects(array $value)
    {
        $found = false;

        array_walk_recursive($value, function ($item) use (&$found) {
            $found = $found || is_object($item);
        });

        return $found;
    }

    /**
     * Apply the "transport_sharing" option supported by the installed SDK.
     *
     * When the installed SDK does not support the option, the "none" and "*_prefer" modes are
     * removed from the configuration, while the "*_require" modes fail loudly.
     *
     * @param  array  $config
     * @return array
     *
     * @throws \RuntimeException
     */
    protected function withTransportSharing(array $config)
    {
        $sharing = $config['transport_sharing'] ?? null;

        if (! is_null($sharing) && class_exists(HttpTransportSharing::class)) {
            return $config;
        }

        if (in_array($sharing, ['handler_require', 'persistent_require'], true)) {
            throw new RuntimeException(
                "The [{$sharing}] transport sharing mode requires an aws/aws-sdk-php version that supports it."
            );
        }

        return Arr::except($config, ['transport_sharing']);
    }
}
