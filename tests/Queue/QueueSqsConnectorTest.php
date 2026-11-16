<?php

namespace Illuminate\Tests\Queue;

use Aws\Credentials\Credentials;
use Aws\Sqs\SqsClient;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Container\Container;
use Illuminate\Queue\Connectors\SqsConnector;
use Illuminate\Queue\SqsQueue;
use Illuminate\Support\Aws\ClientFactory;
use PHPUnit\Framework\TestCase;

class QueueSqsConnectorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Container::setInstance(new Container);
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);

        parent::tearDown();
    }

    public function testConnectionsAreCreatedThroughTheClientFactory()
    {
        $queue = $this->connect([
            'name' => 'reports',
            'key' => 'foo',
            'secret' => 'bar',
            'token' => 'baz',
            'prefix' => 'https://sqs.us-east-2.amazonaws.com/123456789012',
            'overflow' => ['enabled' => true],
            'credential_cache' => ['enabled' => false],
        ], $received, $context);

        $this->assertInstanceOf(SqsQueue::class, $queue);
        $this->assertSame('queue.connections.reports', $context);
        $this->assertSame(['key' => 'foo', 'secret' => 'bar', 'token' => 'baz'], $received['credentials']);
        $this->assertSame(['timeout' => 60, 'connect_timeout' => 60], $received['http']);
        $this->assertArrayNotHasKey('version', $received);
        $this->assertArrayNotHasKey('token', $received);
        $this->assertArrayNotHasKey('credential_cache', $received);
    }

    public function testConnectionsWithoutANameUseTheOnDemandContext()
    {
        $this->connect([], $received, $context);

        $this->assertSame('queue.connections.ondemand', $context);
    }

    public function testConnectionHttpOptionsReplaceTheDefaultHttpOptions()
    {
        $this->connect(['http' => ['verify' => false]], $received);

        $this->assertSame(['verify' => false], $received['http']);
    }

    public function testGlobalHttpOptionsOverrideTheDefaultHttpOptions()
    {
        Container::getInstance()->make(ClientFactory::class)->globalOptions(['http' => ['timeout' => 5, 'proxy' => 'http://proxy']]);

        Container::getInstance()->make(ClientFactory::class)->resolveUsing(SqsClient::class, function ($config) use (&$received) {
            $received = $config;

            return new SqsClient($config);
        });

        (new SqsConnector)->connect($this->config([]));

        $this->assertSame(['timeout' => 5, 'connect_timeout' => 60, 'proxy' => 'http://proxy'], $received['http']);
    }

    public function testCredentialsAreServedFromTheSharedCache()
    {
        $repository = new Repository(new ArrayStore);
        Container::getInstance()->instance('cache', new class($repository)
        {
            public function __construct(public $repository)
            {
            }

            public function store($name = null)
            {
                return $this->repository;
            }
        });

        $config = ['credentials' => 'ecs', 'credential_cache' => ['enabled' => true]];

        $repository->forever(
            Container::getInstance()->make(ClientFactory::class)->credentialsCacheKey(
                SqsClient::class, $this->config($config), 'queue.connections.ondemand'
            ),
            new Credentials('cached-key', 'cached-secret', 'cached-token', time() + 3600),
        );

        $credentials = (new SqsConnector)->connect($this->config($config))->getSqs()->getCredentials()->wait();

        $this->assertSame('cached-key', $credentials->getAccessKeyId());
    }

    /**
     * Connect with the given config, capturing the configuration and context the SQS client is created with.
     */
    protected function connect(array $config, &$received = null, &$context = null): SqsQueue
    {
        Container::getInstance()->make(ClientFactory::class)->resolveUsing(SqsClient::class, function ($config) use (&$received) {
            $received = $config;

            return new SqsClient($config);
        });

        Container::getInstance()->make(ClientFactory::class)->globalOptions(function ($client, $config, $given) use (&$context) {
            $context = $given;

            return [];
        });

        return (new SqsConnector)->connect($this->config($config));
    }

    /**
     * Get a connection config with the given options.
     */
    protected function config(array $config): array
    {
        return array_merge(['driver' => 'sqs', 'region' => 'us-east-2', 'queue' => 'default'], $config);
    }
}
