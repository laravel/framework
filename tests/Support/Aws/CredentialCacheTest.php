<?php

namespace Illuminate\Tests\Support\Aws;

use Aws\Credentials\Credentials;
use GuzzleHttp\Promise\Create;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Support\Aws\CredentialCache;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class CredentialCacheTest extends TestCase
{
    public function testCredentialCacheHitsDoNotInvokeTheUnderlyingProvider()
    {
        $repository = new Repository(new ArrayStore);
        $repository->forever('credentials', new Credentials('cached-key', 'cached-secret', 'cached-token', time() + 3600));

        $provider = fn () => (new CredentialCache(fn () => $repository))->resolve(
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

        $credentials = (new CredentialCache(fn () => $repository))->resolve(
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
        $cache = new CredentialCache(fn () => $repository);
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
        $cache = new CredentialCache(fn () => $repository);
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
        $provider = fn () => (new CredentialCache(
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
        $provider = fn () => (new CredentialCache(
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

        $cache = new CredentialCache(fn () => $primary, fn () => $fallback);

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

        $cache = new CredentialCache(fn () => $primary, fn () => $fallback);

        $credentials = $cache->resolve(
            'credentials',
            fn () => $this->fail('The underlying provider should not be invoked on a cache hit.'),
        )->wait();

        $this->assertSame('primary-key', $credentials->getAccessKeyId());
    }

    public function testCredentialResolutionFallsBackToADirectFetchWhenEveryCacheStoreIsUnavailable()
    {
        $provider = fn () => (new CredentialCache(
            fn () => throw new RuntimeException('Cache store is down.'),
            fn () => throw new RuntimeException('Fallback store is down too.'),
        ))->resolve(
            'credentials',
            fn () => Create::promiseFor(new Credentials('direct-key', 'direct-secret')),
        );

        $this->assertSame('direct-key', $provider()->wait()->getAccessKeyId());
    }
}
