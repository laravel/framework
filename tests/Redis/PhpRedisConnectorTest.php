<?php

namespace Illuminate\Tests\Redis;

use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Redis\Connectors\PhpRedisConnector;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use RedisException;
use ReflectionProperty;

class PhpRedisConnectorTest extends TestCase
{
    public function testNormalizeContextWrapsFlatArrayInStream()
    {
        $connector = new TestablePhpRedisConnector;

        $result = $connector->testNormalizeContext([
            'verify_peer' => false,
            'verify_peer_name' => false,
        ]);

        $this->assertSame([
            'stream' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ], $result);
    }

    public function testNormalizeContextConvertsSslKeyToStream()
    {
        $connector = new TestablePhpRedisConnector;

        $result = $connector->testNormalizeContext([
            'ssl' => [
                'verify_peer' => false,
                'cafile' => '/path/to/ca.pem',
            ],
        ]);

        $this->assertSame([
            'stream' => [
                'verify_peer' => false,
                'cafile' => '/path/to/ca.pem',
            ],
        ], $result);
    }

    public function testNormalizeContextPassesThroughStreamKey()
    {
        $connector = new TestablePhpRedisConnector;

        $context = [
            'stream' => [
                'verify_peer' => false,
            ],
        ];

        $result = $connector->testNormalizeContext($context);

        $this->assertSame($context, $result);
    }

    public function testNormalizeContextSslKeyTakesPrecedenceOverFlatKeys()
    {
        $connector = new TestablePhpRedisConnector;

        $result = $connector->testNormalizeContext([
            'verify_peer' => true,
            'ssl' => [
                'verify_peer' => false,
            ],
        ]);

        $this->assertSame([
            'stream' => [
                'verify_peer' => false,
            ],
        ], $result);
    }

    public function testNormalizeClusterContextUnwrapsSslKey()
    {
        $connector = new TestablePhpRedisConnector;

        $result = $connector->testNormalizeClusterContext([
            'ssl' => [
                'verify_peer' => false,
                'peer_name' => 'example.com',
            ],
        ]);

        $this->assertSame([
            'verify_peer' => false,
            'peer_name' => 'example.com',
        ], $result);
    }

    public function testNormalizeClusterContextUnwrapsStreamKey()
    {
        $connector = new TestablePhpRedisConnector;

        $result = $connector->testNormalizeClusterContext([
            'stream' => [
                'verify_peer' => false,
            ],
        ]);

        $this->assertSame([
            'verify_peer' => false,
        ], $result);
    }

    public function testNormalizeClusterContextPassesThroughFlatArray()
    {
        $connector = new TestablePhpRedisConnector;

        $context = [
            'verify_peer' => false,
            'verify_peer_name' => false,
        ];

        $result = $connector->testNormalizeClusterContext($context);

        $this->assertSame($context, $result);
    }

    public function testNormalizeClusterContextSslKeyTakesPrecedenceOverFlatKeys()
    {
        $connector = new TestablePhpRedisConnector;

        $result = $connector->testNormalizeClusterContext([
            'verify_peer' => true,
            'ssl' => [
                'verify_peer' => false,
            ],
        ]);

        $this->assertSame([
            'verify_peer' => false,
        ], $result);
    }

    public function testFormatClusterPasswordReturnsArrayWhenUsernameAndPasswordProvided()
    {
        $connector = new TestablePhpRedisConnector;

        $result = $connector->testFormatClusterPassword([
            'username' => 'myuser',
            'password' => 'mypass',
        ]);

        $this->assertSame(['myuser', 'mypass'], $result);
    }

    public function testFormatClusterPasswordReturnsPlainPasswordWithoutUsername()
    {
        $connector = new TestablePhpRedisConnector;

        $result = $connector->testFormatClusterPassword([
            'password' => 'mypass',
        ]);

        $this->assertSame('mypass', $result);
    }

    public function testFormatClusterPasswordReturnsNullWhenNoPasswordProvided()
    {
        $connector = new TestablePhpRedisConnector;

        $result = $connector->testFormatClusterPassword([]);

        $this->assertNull($result);
    }

    public function testFormatClusterPasswordReturnsPlainPasswordWhenUsernameIsEmpty()
    {
        $connector = new TestablePhpRedisConnector;

        $result = $connector->testFormatClusterPassword([
            'username' => '',
            'password' => 'mypass',
        ]);

        $this->assertSame('mypass', $result);
    }

    public function testFormatClusterPasswordReturnsPlainPasswordWhenPasswordIsNotString()
    {
        $connector = new TestablePhpRedisConnector;

        $result = $connector->testFormatClusterPassword([
            'username' => 'myuser',
            'password' => ['mypass'],
        ]);

        $this->assertSame(['mypass'], $result);
    }

    public function testParseBackoffAlgorithmReturnsIntegerAsIs()
    {
        $connector = new TestablePhpRedisConnector;

        $this->assertSame(42, $connector->testParseBackoffAlgorithm(42));
    }

    #[RequiresPhpExtension('redis')]
    public function testParseBackoffAlgorithmParsesValidNames(): void
    {
        $connector = new TestablePhpRedisConnector;

        $this->assertSame(\Redis::BACKOFF_ALGORITHM_DEFAULT, $connector->testParseBackoffAlgorithm('default'));
        $this->assertSame(\Redis::BACKOFF_ALGORITHM_DECORRELATED_JITTER, $connector->testParseBackoffAlgorithm('decorrelated_jitter'));
        $this->assertSame(\Redis::BACKOFF_ALGORITHM_EQUAL_JITTER, $connector->testParseBackoffAlgorithm('equal_jitter'));
        $this->assertSame(\Redis::BACKOFF_ALGORITHM_EXPONENTIAL, $connector->testParseBackoffAlgorithm('exponential'));
        $this->assertSame(\Redis::BACKOFF_ALGORITHM_UNIFORM, $connector->testParseBackoffAlgorithm('uniform'));
        $this->assertSame(\Redis::BACKOFF_ALGORITHM_CONSTANT, $connector->testParseBackoffAlgorithm('constant'));
    }

    public function testParseBackoffAlgorithmThrowsForInvalidName()
    {
        $connector = new TestablePhpRedisConnector;

        $this->expectExceptionObject(new InvalidArgumentException('Algorithm [bogus] is not a valid PhpRedis backoff algorithm.'));

        $connector->testParseBackoffAlgorithm('bogus');
    }

    public function testFormatHostPrefixesConfiguredSchemeWhenHostHasNoScheme()
    {
        $connector = new TestablePhpRedisConnector;

        $this->assertSame('tls://127.0.0.1', $connector->testFormatHost([
            'host' => '127.0.0.1',
            'scheme' => 'tls',
        ]));
    }

    public function testFormatHostDoesNotDuplicateMatchingScheme()
    {
        $connector = new TestablePhpRedisConnector;

        $this->assertSame('tls://127.0.0.1', $connector->testFormatHost([
            'host' => 'tls://127.0.0.1',
            'scheme' => 'tls',
        ]));
    }

    public function testFormatHostThrowsOnConflictingScheme()
    {
        $connector = new TestablePhpRedisConnector;

        $this->expectExceptionObject(new InvalidArgumentException('The scheme configured in the Redis host option must match the scheme option.'));

        $connector->testFormatHost([
            'host' => 'tcp://127.0.0.1',
            'scheme' => 'tls',
        ]);
    }

    public function testFormatHostAllowsCaseInsensitiveMatchingScheme()
    {
        $connector = new TestablePhpRedisConnector;

        $this->assertSame('TLS://127.0.0.1', $connector->testFormatHost([
            'host' => 'TLS://127.0.0.1',
            'scheme' => 'tls',
        ]));
    }

    public function testFormatHostThrowsWhenHostIsMissing()
    {
        $connector = new TestablePhpRedisConnector;

        $this->expectExceptionObject(new InvalidArgumentException('Redis host must be a non-empty string.'));

        $connector->testFormatHost([
            'scheme' => 'tls',
        ]);
    }

    public function testFormatHostThrowsWhenHostIsNull()
    {
        $connector = new TestablePhpRedisConnector;

        $this->expectExceptionObject(new InvalidArgumentException('Redis host must be a non-empty string.'));

        $connector->testFormatHost([
            'host' => null,
            'scheme' => 'tls',
        ]);
    }

    public function testConnectToClusterPassesAConnectorToTheConnection()
    {
        $connector = new ClusterStubPhpRedisConnector;

        $connection = $connector->connectToCluster([['host' => '127.0.0.1', 'port' => 6379]], [], []);

        $property = new ReflectionProperty(PhpRedisConnection::class, 'connector');

        $this->assertIsCallable($property->getValue($connection));
    }

    #[RequiresPhpExtension('redis')]
    public function testConnectToClusterAllowsTheConnectionToRebuildItsClient()
    {
        $connector = new ClusterStubPhpRedisConnector;

        $connection = $connector->connectToCluster([['host' => '127.0.0.1', 'port' => 6379]], [], []);

        $original = $connection->client();

        try {
            $connection->command('get', ['foo']);
        } catch (RedisException) {
            //
        }

        $this->assertSame(3, $connector->created);
        $this->assertNotSame($original, $connection->client());
    }

    #[RequiresPhpExtension('redis')]
    public function testConnectionAutomaticallyRetriesReadOnlyCommandAfterRebuildingItsClient()
    {
        $failedClient = $this->createMock(\Redis::class);
        $failedClient->expects($this->once())->method('get')->with('foo')->willThrowException(new RedisException('Connection lost'));

        $healthyClient = $this->createMock(\Redis::class);
        $healthyClient->expects($this->once())->method('get')->with('foo')->willReturn('bar');

        $connection = new PhpRedisConnection($failedClient, fn () => $healthyClient);

        $this->assertSame('bar', $connection->command('get', ['foo']));
        $this->assertSame($healthyClient, $connection->client());
    }

    #[RequiresPhpExtension('redis')]
    public function testConnectionAutomaticallyRetriesIdempotentWriteAfterRebuildingItsClient()
    {
        $failedClient = $this->createMock(\Redis::class);
        $failedClient->expects($this->once())->method('set')->with('foo', 'bar')->willThrowException(new RedisException('Connection lost'));

        $healthyClient = $this->createMock(\Redis::class);
        $healthyClient->expects($this->once())->method('set')->with('foo', 'bar')->willReturn(true);

        $connection = new PhpRedisConnection($failedClient, fn () => $healthyClient);

        $this->assertTrue($connection->command('set', ['foo', 'bar']));
    }

    #[RequiresPhpExtension('redis')]
    public function testConnectionDoesNotAutomaticallyRetryNonIdempotentWrite()
    {
        $failedClient = $this->createMock(\Redis::class);
        $failedClient->expects($this->once())->method('incr')->with('foo')->willThrowException(new RedisException('Connection lost'));

        $healthyClient = $this->createMock(\Redis::class);
        $healthyClient->expects($this->never())->method('incr');

        $connection = new PhpRedisConnection($failedClient, fn () => $healthyClient);

        $this->expectExceptionObject(new RedisException('Connection lost'));

        $connection->command('incr', ['foo']);
    }

    #[RequiresPhpExtension('redis')]
    public function testConnectionDoesNotAutomaticallyRetrySetWithOptions()
    {
        $failedClient = $this->createMock(\Redis::class);
        $failedClient->expects($this->once())->method('set')->with('foo', 'bar', ['ex' => 60])->willThrowException(new RedisException('Connection lost'));

        $healthyClient = $this->createMock(\Redis::class);
        $healthyClient->expects($this->never())->method('set');

        $connection = new PhpRedisConnection($failedClient, fn () => $healthyClient);

        $this->expectExceptionObject(new RedisException('Connection lost'));

        $connection->command('set', ['foo', 'bar', ['ex' => 60]]);
    }

    #[RequiresPhpExtension('redis')]
    public function testConnectionStopsRetryingAfterConfiguredAttempts()
    {
        $clients = [];

        for ($i = 0; $i < 4; $i++) {
            $clients[$i] = $this->createMock(\Redis::class);

            if ($i < 3) {
                $clients[$i]->expects($this->once())->method('get')->with('foo')->willThrowException(new RedisException('Connection lost'));
            }
        }

        $reconnects = 0;
        $connection = new PhpRedisConnection($clients[0], function () use (&$reconnects, $clients) {
            return $clients[++$reconnects];
        }, ['command_retries' => 2]);

        try {
            $connection->command('get', ['foo']);
            $this->fail('Expected RedisException was not thrown.');
        } catch (RedisException $e) {
            $this->assertSame('Connection lost', $e->getMessage());
        }

        $this->assertSame(3, $reconnects);
    }

    #[RequiresPhpExtension('redis')]
    public function testConnectionRebuildsItsClientAfterAClusterResponseError()
    {
        $failedClient = $this->createMock(\Redis::class);
        $failedClient->expects($this->once())->method('get')->with('foo')->willThrowException(new \RedisClusterException('Error processing response from Redis node!'));

        $healthyClient = $this->createMock(\Redis::class);
        $healthyClient->expects($this->once())->method('get')->with('foo')->willReturn('bar');

        $connection = new PhpRedisConnection($failedClient, fn () => $healthyClient);

        $this->assertSame('bar', $connection->command('get', ['foo']));
        $this->assertSame($healthyClient, $connection->client());
    }

    public function testConnectToSentinelResolvesTheMaster()
    {
        $connector = new SentinelStubPhpRedisConnector([
            'sentinel-1:26379' => fn () => ['10.0.0.9', '6380'],
        ]);

        $connection = $connector->connectToSentinel([
            'hosts' => [['host' => 'sentinel-1', 'port' => 26379]],
            'service' => 'mymaster',
            'database' => 2,
        ], []);

        $this->assertInstanceOf(PhpRedisConnection::class, $connection);
        $this->assertSame(['sentinel-1:26379'], $connector->queried);
        $this->assertSame('10.0.0.9', $connector->lastClientConfig['host']);
        $this->assertSame(6380, $connector->lastClientConfig['port']);
        $this->assertSame(2, $connector->lastClientConfig['database']);
    }

    #[RequiresPhpExtension('redis')]
    public function testConnectToSentinelSkipsSentinelsThatCannotNameTheMaster()
    {
        $connector = new SentinelStubPhpRedisConnector([
            'sentinel-1:26379' => fn () => throw new RedisException('Connection refused'),
            'sentinel-2:26379' => fn () => false,
            'sentinel-3:26379' => fn () => ['10.0.0.9', '6380'],
        ]);

        $connector->connectToSentinel([
            'hosts' => [['host' => 'sentinel-1'], ['host' => 'sentinel-2', 'port' => 26379], ['host' => 'sentinel-3', 'port' => '26379']],
        ], []);

        $this->assertSame(['sentinel-1:26379', 'sentinel-2:26379', 'sentinel-3:26379'], $connector->queried);
        $this->assertSame(['10.0.0.9:6380'], $connector->clients);
    }

    #[RequiresPhpExtension('redis')]
    public function testConnectToSentinelThrowsWhenNoSentinelKnowsTheMaster()
    {
        $connector = new SentinelStubPhpRedisConnector([
            'sentinel-1:26379' => fn () => throw new RedisException('Connection refused'),
            'sentinel-2:26379' => fn () => false,
        ]);

        try {
            $connector->connectToSentinel(['hosts' => [['host' => 'sentinel-1'], ['host' => 'sentinel-2']]], []);
            $this->fail('Expected RedisException was not thrown.');
        } catch (RedisException $e) {
            $this->assertStringContainsString('service [mymaster]', $e->getMessage());
            $this->assertStringContainsString('sentinel-1:26379 (Connection refused)', $e->getMessage());
            $this->assertStringContainsString('sentinel-2:26379 (no master known for service [mymaster])', $e->getMessage());
        }

        $this->assertSame([], $connector->clients);
    }

    #[RequiresPhpExtension('redis')]
    public function testSentinelConnectionResolvesTheMasterAgainWhenRebuildingItsClient()
    {
        $masters = [['10.0.0.9', '6380'], ['10.0.0.2', '6380']];

        $connector = new SentinelStubPhpRedisConnector([
            'sentinel-1:26379' => function () use (&$masters) {
                return array_shift($masters);
            },
        ], ['10.0.0.9']);

        $connection = $connector->connectToSentinel(['hosts' => [['host' => 'sentinel-1']]], []);

        $this->assertSame('bar', $connection->command('get', ['foo']));
        $this->assertSame(['sentinel-1:26379', 'sentinel-1:26379'], $connector->queried);
        $this->assertSame(['10.0.0.9:6380', '10.0.0.2:6380'], $connector->clients);
    }

    #[RequiresPhpExtension('redis')]
    public function testCreateSentinelReturnsARedisSentinelInstance()
    {
        $connector = new TestablePhpRedisConnector;

        $this->assertInstanceOf(\RedisSentinel::class, $connector->testCreateSentinel('127.0.0.1', 26379, ['timeout' => 0.5]));
    }
}

class SentinelStubPhpRedisConnector extends PhpRedisConnector
{
    public array $queried = [];

    public array $clients = [];

    public array $lastClientConfig = [];

    public function __construct(protected array $sentinels = [], protected array $deadHosts = [])
    {
    }

    protected function createClient(array $config)
    {
        $this->clients[] = $config['host'].':'.$config['port'];
        $this->lastClientConfig = $config;

        return new class($config['host'], $this->deadHosts)
        {
            public function __construct(protected string $host, protected array $deadHosts)
            {
            }

            public function get($key)
            {
                if (in_array($this->host, $this->deadHosts, true)) {
                    throw new RedisException('Connection lost');
                }

                return 'bar';
            }
        };
    }

    protected function createSentinel($host, $port, array $config)
    {
        $this->queried[] = "{$host}:{$port}";

        $answer = $this->sentinels["{$host}:{$port}"] ?? fn () => throw new RedisException('Connection refused');

        return new class($answer)
        {
            public function __construct(protected $answer)
            {
            }

            public function getMasterAddrByName($service)
            {
                return ($this->answer)($service);
            }
        };
    }
}

class ClusterStubPhpRedisConnector extends PhpRedisConnector
{
    public int $created = 0;

    protected function createRedisClusterInstance(array $servers, array $options)
    {
        $this->created++;

        return new class
        {
            public function get($key)
            {
                throw new RedisException('Connection lost');
            }
        };
    }
}

class TestablePhpRedisConnector extends PhpRedisConnector
{
    public function testNormalizeContext(array $context): array
    {
        return $this->normalizeContext($context);
    }

    public function testNormalizeClusterContext(array $context): array
    {
        return $this->normalizeClusterContext($context);
    }

    public function testFormatClusterPassword(array $options)
    {
        return $this->formatClusterPassword($options);
    }

    public function testParseBackoffAlgorithm(mixed $algorithm): int
    {
        return $this->parseBackoffAlgorithm($algorithm);
    }

    public function testFormatHost(array $options): string
    {
        return $this->formatHost($options);
    }

    public function testCreateSentinel(string $host, int $port, array $config)
    {
        return $this->createSentinel($host, $port, $config);
    }
}
