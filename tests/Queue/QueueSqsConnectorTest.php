<?php

namespace Illuminate\Tests\Queue;

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
            'credential_cache' => ['enabled' => true],
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
            'credential_cache' => ['enabled' => false],
        ]);

        $this->assertArrayNotHasKey('credentials', $config);
    }

    public function testExplicitCredentialsAreLeftUntouched()
    {
        $config = $this->credentials([
            'credentials' => false,
            'credential_cache' => ['enabled' => true],
        ]);

        $this->assertFalse($config['credentials']);
    }

    public function testStaticKeyAndSecretCredentialsAreLeftUntouched()
    {
        $config = $this->credentials([
            'key' => 'static-key',
            'secret' => 'static-secret',
            'credential_cache' => ['enabled' => true],
        ]);

        $this->assertSame(['key' => 'static-key', 'secret' => 'static-secret'], $config['credentials']);
    }

    public function testCredentialCacheHitsDoNotInvokeTheUnderlyingProvider()
    {
        $repository = new Repository(new ArrayStore);
        $repository->forever('credentials', new Credentials('cached-key', 'cached-secret', 'cached-token', time() + 3600));

        $provider = fn () => (new AwsCredentialCache(fn () => $repository))->resolve(
            'credentials',
            fn () => $this->fail('The underlying provider should not be invoked on a cache hit.'),
        );

        $this->assertSame('cached-key', $provider()->wait()->getAccessKeyId());
    }

    public function testCredentialResolutionRefreshesCredentialsBeforeTheyExpire()
    {
        $repository = new Repository(new ArrayStore);
        $repository->forever('credentials', new Credentials('expiring-key', 'expiring-secret', expires: time() + 30));
        $calls = 0;

        $credentials = (new AwsCredentialCache(fn () => $repository))->resolve(
            'credentials',
            function () use (&$calls) {
                $calls++;

                return Create::promiseFor(new Credentials('fresh-key', 'fresh-secret', expires: time() + 3600));
            },
        )->wait();

        $this->assertSame(1, $calls);
        $this->assertSame('fresh-key', $credentials->getAccessKeyId());
        $this->assertSame('fresh-key', $repository->get('credentials')->getAccessKeyId());
    }

    public function testCredentialResolutionReusesCredentialsRefreshedByAnotherProcess()
    {
        $repository = new Repository(new ArrayStore);
        $cache = new AwsCredentialCache(fn () => $repository);
        $calls = 0;
        $provider = function () use (&$calls) {
            $calls++;

            return Create::promiseFor(new Credentials('shared-key', 'shared-secret', expires: time() + 3600));
        };

        $first = $cache->resolve('credentials', $provider)->wait();
        $second = $cache->resolve('credentials', $provider)->wait();

        $this->assertSame(1, $calls);
        $this->assertSame('shared-key', $first->getAccessKeyId());
        $this->assertSame('shared-key', $second->getAccessKeyId());
    }

    public function testCredentialResolutionCachesCredentialsWithoutAnExpiration()
    {
        $repository = new Repository(new ArrayStore);
        $cache = new AwsCredentialCache(fn () => $repository);
        $calls = 0;
        $provider = function () use (&$calls) {
            $calls++;

            return Create::promiseFor(new Credentials('constant-key', 'constant-secret'));
        };

        $cache->resolve('credentials', $provider)->wait();
        $credentials = $cache->resolve('credentials', $provider)->wait();

        $this->assertSame(1, $calls);
        $this->assertSame('constant-key', $credentials->getAccessKeyId());
    }

    public function testCredentialResolutionFallsBackToADirectFetchWhenTheCacheStoreIsUnavailable()
    {
        // A broken cache backend (e.g. Redis down) must behave exactly like a
        // cache miss rather than breaking credential resolution.
        $provider = fn () => (new AwsCredentialCache(
            fn () => throw new RuntimeException('Cache store is down.'),
        ))->resolve(
            'credentials',
            fn () => Create::promiseFor(new Credentials('direct-key', 'direct-secret')),
        );

        $this->assertSame('direct-key', $provider()->wait()->getAccessKeyId());
    }

    public function testCredentialsAreServedFromTheFallbackStoreWhenThePrimaryStoreIsUnavailable()
    {
        $fallback = new Repository(new ArrayStore);
        $fallback->forever('credentials', new Credentials('fallback-key', 'fallback-secret', 'fallback-token', time() + 3600));

        // With the primary store down, the fallback keeps credential fetches
        // deduplicated rather than degrading straight to one fetch per process.
        $provider = fn () => (new AwsCredentialCache(
            fn () => throw new RuntimeException('Cache store is down.'),
            fn () => $fallback,
        ))->resolve(
            'credentials',
            fn () => $this->fail('The underlying provider should not be invoked on a fallback cache hit.'),
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
        $cache->resolve(
            'credentials',
            fn () => Create::promiseFor(new Credentials('shared-key', 'shared-secret', expires: time() + 3600)),
        )->wait();

        $this->assertSame('shared-key', $primary->get('credentials')->getAccessKeyId());
        $this->assertSame('shared-key', $fallback->get('credentials')->getAccessKeyId());
    }

    public function testThePrimaryStoreIsPreferredOverTheFallbackStore()
    {
        $primary = new Repository(new ArrayStore);
        $primary->forever('credentials', new Credentials('primary-key', 'primary-secret', expires: time() + 3600));

        $fallback = new Repository(new ArrayStore);
        $fallback->forever('credentials', new Credentials('fallback-key', 'fallback-secret', expires: time() + 3600));

        $cache = new AwsCredentialCache(fn () => $primary, fn () => $fallback);

        $credentials = $cache->resolve(
            'credentials',
            fn () => $this->fail('The underlying provider should not be invoked on a cache hit.'),
        )->wait();

        $this->assertSame('primary-key', $credentials->getAccessKeyId());
    }

    public function testCredentialResolutionFallsBackToADirectFetchWhenEveryCacheStoreIsUnavailable()
    {
        $provider = fn () => (new AwsCredentialCache(
            fn () => throw new RuntimeException('Cache store is down.'),
            fn () => throw new RuntimeException('Fallback store is down too.'),
        ))->resolve(
            'credentials',
            fn () => Create::promiseFor(new Credentials('direct-key', 'direct-secret')),
        );

        $this->assertSame('direct-key', $provider()->wait()->getAccessKeyId());
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
                return $this->withCredentials($this->getDefaultConfiguration($config));
            }
        };

        return $connector->credentials(array_merge([
            'driver' => 'sqs',
            'region' => 'us-east-2',
            'queue' => 'default',
        ], $config));
    }
}
