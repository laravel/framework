<?php

namespace Illuminate\Tests\Queue;

use Aws\Credentials\CredentialProvider;
use Aws\Credentials\Credentials;
use Closure;
use GuzzleHttp\Promise\Create;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Queue\AwsCredentialCache;
use Illuminate\Queue\Connectors\SqsConnector;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class QueueSqsConnectorTest extends TestCase
{
    public function testCredentialsAreWrappedWithASharedCacheProviderWhenCachingIsEnabled()
    {
        $config = $this->credentials([
            'credentials_cache' => ['enabled' => true],
        ]);

        $this->assertInstanceOf(Closure::class, $config['credentials']);
    }

    public function testCredentialsAreNotWrappedWhenCachingIsNotConfigured()
    {
        $config = $this->credentials([]);

        $this->assertArrayNotHasKey('credentials', $config);
    }

    public function testCredentialsAreNotWrappedWhenCachingIsDisabled()
    {
        $config = $this->credentials([
            'credentials_cache' => ['enabled' => false],
        ]);

        $this->assertArrayNotHasKey('credentials', $config);
    }

    public function testExplicitCredentialsAreLeftUntouched()
    {
        $config = $this->credentials([
            'credentials' => false,
            'credentials_cache' => ['enabled' => true],
        ]);

        $this->assertFalse($config['credentials']);
    }

    public function testStaticKeyAndSecretCredentialsAreLeftUntouched()
    {
        $config = $this->credentials([
            'key' => 'static-key',
            'secret' => 'static-secret',
            'credentials_cache' => ['enabled' => true],
        ]);

        $this->assertSame(['key' => 'static-key', 'secret' => 'static-secret'], $config['credentials']);
    }

    public function testCredentialCacheHitsDoNotInvokeTheUnderlyingProvider()
    {
        $repository = new Repository(new ArrayStore);
        $repository->forever('credentials', new Credentials('cached-key', 'cached-secret', 'cached-token', time() + 3600));

        $provider = CredentialProvider::cache(
            fn () => $this->fail('The underlying provider should not be invoked on a cache hit.'),
            new AwsCredentialCache(fn () => $repository),
            'credentials',
        );

        $this->assertSame('cached-key', $provider()->wait()->getAccessKeyId());
    }

    public function testCredentialResolutionFallsBackToADirectFetchWhenTheCacheStoreIsUnavailable()
    {
        // A broken cache backend (e.g. Redis down) must behave exactly like a
        // cache miss rather than breaking credential resolution.
        $provider = CredentialProvider::cache(
            fn () => Create::promiseFor(new Credentials('direct-key', 'direct-secret')),
            new AwsCredentialCache(fn () => throw new RuntimeException('Cache store is down.')),
            'credentials',
        );

        $this->assertSame('direct-key', $provider()->wait()->getAccessKeyId());
    }

    public function testCredentialsAreServedFromTheFallbackStoreWhenThePrimaryStoreIsUnavailable()
    {
        $fallback = new Repository(new ArrayStore);
        $fallback->forever('credentials', new Credentials('fallback-key', 'fallback-secret', 'fallback-token', time() + 3600));

        // With the primary store down, the fallback keeps credential fetches
        // deduplicated rather than degrading straight to one fetch per process.
        $provider = CredentialProvider::cache(
            fn () => $this->fail('The underlying provider should not be invoked on a fallback cache hit.'),
            new AwsCredentialCache(
                fn () => throw new RuntimeException('Cache store is down.'),
                fn () => $fallback,
            ),
            'credentials',
        );

        $this->assertSame('fallback-key', $provider()->wait()->getAccessKeyId());
    }

    public function testCredentialsAreWrittenThroughToTheFallbackStore()
    {
        $primary = new Repository(new ArrayStore);
        $fallback = new Repository(new ArrayStore);

        $cache = new AwsCredentialCache(fn () => $primary, fn () => $fallback);

        // The fallback is written through on every set so it is already warm
        // when the primary store becomes unavailable.
        $cache->set('credentials', 'value', 60);
        $this->assertSame('value', $primary->get('credentials'));
        $this->assertSame('value', $fallback->get('credentials'));

        $cache->remove('credentials');
        $this->assertNull($primary->get('credentials'));
        $this->assertNull($fallback->get('credentials'));
    }

    public function testThePrimaryStoreIsPreferredOverTheFallbackStore()
    {
        $primary = new Repository(new ArrayStore);
        $primary->forever('credentials', 'primary-value');

        $fallback = new Repository(new ArrayStore);
        $fallback->forever('credentials', 'fallback-value');

        $cache = new AwsCredentialCache(fn () => $primary, fn () => $fallback);

        $this->assertSame('primary-value', $cache->get('credentials'));
    }

    public function testCredentialResolutionFallsBackToADirectFetchWhenEveryCacheStoreIsUnavailable()
    {
        $provider = CredentialProvider::cache(
            fn () => Create::promiseFor(new Credentials('direct-key', 'direct-secret')),
            new AwsCredentialCache(
                fn () => throw new RuntimeException('Cache store is down.'),
                fn () => throw new RuntimeException('Fallback store is down too.'),
            ),
            'credentials',
        );

        $this->assertSame('direct-key', $provider()->wait()->getAccessKeyId());
    }

    public function testCredentialCacheStoresAndRemovesThroughTheRepository()
    {
        $repository = new Repository(new ArrayStore);

        $cache = new AwsCredentialCache(fn () => $repository);

        $cache->set('credentials', 'value', 60);
        $this->assertSame('value', $cache->get('credentials'));

        $cache->remove('credentials');
        $this->assertNull($cache->get('credentials'));
    }

    public function testCredentialsCacheKeyIsScopedToThePodIdentityAndConnection()
    {
        $_SERVER['AWS_CONTAINER_CREDENTIALS_FULL_URI'] = 'http://169.254.170.23/v1/credentials';

        try {
            $key = SqsConnector::credentialsCacheKey(['region' => 'us-east-2', 'prefix' => 'prefix']);

            $this->assertNotSame($key, SqsConnector::credentialsCacheKey(['region' => 'us-west-2', 'prefix' => 'prefix']));
            $this->assertNotSame($key, SqsConnector::credentialsCacheKey(['region' => 'us-east-2', 'prefix' => 'other-prefix']));

            $_SERVER['AWS_CONTAINER_CREDENTIALS_FULL_URI'] = 'http://169.254.170.23/v1/other-credentials';

            $this->assertNotSame($key, SqsConnector::credentialsCacheKey(['region' => 'us-east-2', 'prefix' => 'prefix']));
        } finally {
            unset($_SERVER['AWS_CONTAINER_CREDENTIALS_FULL_URI']);
        }
    }

    /**
     * Run the given connection config through the connector's credential resolution.
     */
    protected function credentials(array $config): array
    {
        $connector = new class extends SqsConnector
        {
            public function credentials(array $config)
            {
                return $this->configureCredentials($this->getDefaultConfiguration($config));
            }
        };

        return $connector->credentials(array_merge([
            'driver' => 'sqs',
            'region' => 'us-east-2',
            'queue' => 'default',
        ], $config));
    }
}
