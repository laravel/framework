<?php

namespace Illuminate\Tests\Redis;

use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Redis\Connectors\PhpRedisSentinelConnector;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use RedisException;
use ReflectionProperty;

class PhpRedisSentinelConnectorTest extends TestCase
{
    #[RequiresPhpExtension('redis')]
    public function testConnectsToMasterResolvedBySentinel()
    {
        $connector = new SentinelStubPhpRedisConnector([
            'sentinel-a' => ['ip' => '10.0.0.9', 'port' => '6381'],
        ]);

        $connection = $connector->connect([
            'sentinel_host' => 'sentinel-a',
            'sentinel_service' => 'mymaster',
        ], []);

        $this->assertInstanceOf(PhpRedisConnection::class, $connection);
        $this->assertSame('10.0.0.9', $connector->established['host']);
        $this->assertSame(6381, $connector->established['port']);
    }

    #[RequiresPhpExtension('redis')]
    public function testDiscoveryFailsOverToNextSentinelHost()
    {
        $connector = new SentinelStubPhpRedisConnector([
            'sentinel-a' => new RedisException('Connection refused'),
            'sentinel-b' => ['ip' => '10.0.0.7', 'port' => '6379'],
        ]);

        $connection = $connector->connect([
            'sentinel_hosts' => [
                ['host' => 'sentinel-a', 'port' => 26379],
                ['host' => 'sentinel-b', 'port' => 26380],
            ],
            'sentinel_service' => 'mymaster',
        ], []);

        $this->assertInstanceOf(PhpRedisConnection::class, $connection);
        $this->assertSame(['sentinel-a', 'sentinel-b'], $connector->attempted);
        $this->assertSame('10.0.0.7', $connector->established['host']);
    }

    #[RequiresPhpExtension('redis')]
    public function testThrowsWhenSentinelKnowsNoMasterForService()
    {
        $connector = new SentinelStubPhpRedisConnector([
            'sentinel-a' => false,
        ]);

        $this->expectExceptionObject(new RedisException('No master found for service [mymaster].'));

        $connector->connect(['sentinel_host' => 'sentinel-a'], []);
    }

    #[RequiresPhpExtension('redis')]
    public function testThrowsLastExceptionWhenAllSentinelsAreUnreachable()
    {
        $connector = new SentinelStubPhpRedisConnector([
            'sentinel-a' => new RedisException('Connection refused by a'),
            'sentinel-b' => new RedisException('Connection refused by b'),
        ]);

        $this->expectExceptionObject(new RedisException('Connection refused by b'));

        $connector->connect([
            'sentinel_hosts' => [
                ['host' => 'sentinel-a', 'port' => 26379],
                ['host' => 'sentinel-b', 'port' => 26379],
            ],
        ], []);
    }

    #[RequiresPhpExtension('redis')]
    public function testSentinelRetryDefaultsAreApplied()
    {
        $connector = new SentinelStubPhpRedisConnector([
            'sentinel-a' => ['ip' => '10.0.0.9', 'port' => '6381'],
        ]);

        $connection = $connector->connect(['sentinel_host' => 'sentinel-a'], []);

        $property = new ReflectionProperty(PhpRedisConnection::class, 'config');
        $config = $property->getValue($connection);

        $this->assertSame(20, $config['command_retries']);
        $this->assertSame(1000, $config['backoff_base']);
        $this->assertSame(1000, $config['backoff_cap']);
    }

    #[RequiresPhpExtension('redis')]
    public function testSentinelRetryDefaultsMayBeOverridden()
    {
        $connector = new SentinelStubPhpRedisConnector([
            'sentinel-a' => ['ip' => '10.0.0.9', 'port' => '6381'],
        ]);

        $connection = $connector->connect([
            'sentinel_host' => 'sentinel-a',
            'command_retries' => 3,
            'backoff_base' => 250,
        ], []);

        $property = new ReflectionProperty(PhpRedisConnection::class, 'config');
        $config = $property->getValue($connection);

        $this->assertSame(3, $config['command_retries']);
        $this->assertSame(250, $config['backoff_base']);
    }

    public function testSentinelParametersForPhpRedis61IncludeSsl()
    {
        $connector = new TestableVersionSentinelConnector('6.1.0');

        [$options] = $connector->testSentinelParameters([
            'sentinel_host' => 'sentinel-a',
            'sentinel_port' => 26380,
            'sentinel_timeout' => 0.5,
            'sentinel_read_timeout' => 1.0,
            'sentinel_ssl' => ['verify_peer' => false],
        ]);

        $this->assertSame('sentinel-a', $options['host']);
        $this->assertSame(26380, $options['port']);
        $this->assertSame(0.5, $options['connectTimeout']);
        $this->assertSame(1.0, $options['readTimeout']);
        $this->assertSame(['verify_peer' => false], $options['ssl']);
    }

    public function testSentinelParametersForPhpRedis60OmitSsl()
    {
        $connector = new TestableVersionSentinelConnector('6.0.2');

        [$options] = $connector->testSentinelParameters([
            'sentinel_host' => 'sentinel-a',
            'sentinel_ssl' => ['verify_peer' => false],
        ]);

        $this->assertSame('sentinel-a', $options['host']);
        $this->assertArrayNotHasKey('ssl', $options);
    }

    public function testSentinelParametersIncludeAuthWhenCredentialsAreConfigured()
    {
        $connector = new TestableVersionSentinelConnector('6.0.2');

        [$options] = $connector->testSentinelParameters([
            'sentinel_host' => 'sentinel-a',
            'sentinel_username' => 'sentinel-user',
            'sentinel_password' => 'secret',
        ]);

        $this->assertSame(['sentinel-user', 'secret'], $options['auth']);

        [$options] = $connector->testSentinelParameters([
            'sentinel_host' => 'sentinel-a',
            'sentinel_password' => 'secret',
        ]);

        $this->assertSame('secret', $options['auth']);
    }

    public function testSentinelParametersForLegacyPhpRedisArePositional()
    {
        $connector = new TestableVersionSentinelConnector('5.3.7');

        $parameters = $connector->testSentinelParameters([
            'sentinel_host' => 'sentinel-a',
            'sentinel_port' => 26380,
        ]);

        $this->assertSame(['sentinel-a', 26380, 0.2, null, 0, 0], $parameters);
    }

    public function testSentinelParametersForLegacyPhpRedisAppendAuth()
    {
        $connector = new TestableVersionSentinelConnector('5.3.7');

        $parameters = $connector->testSentinelParameters([
            'sentinel_host' => 'sentinel-a',
            'sentinel_password' => 'secret',
        ]);

        $this->assertCount(7, $parameters);
        $this->assertSame('secret', $parameters[6]);
    }

    public function testSentinelParametersRequireAHost()
    {
        $connector = new TestableVersionSentinelConnector('6.1.0');

        $this->expectExceptionObject(new InvalidArgumentException('Redis Sentinel host must be a non-empty string.'));

        $connector->testSentinelParameters([]);
    }
}

class SentinelStubPhpRedisConnector extends PhpRedisSentinelConnector
{
    public $attempted = [];

    public $established;

    public function __construct(protected array $sentinels = [])
    {
    }

    protected function connectToSentinel(array $config)
    {
        $host = $config['sentinel_host'];

        $this->attempted[] = $host;

        $outcome = $this->sentinels[$host] ?? new RedisException('Connection refused');

        if ($outcome instanceof RedisException) {
            throw $outcome;
        }

        return new class($outcome)
        {
            public function __construct(private $master)
            {
            }

            public function master($service)
            {
                return $this->master;
            }
        };
    }

    protected function establishConnection($client, array $config)
    {
        $this->established = $config;
    }
}

class TestableVersionSentinelConnector extends PhpRedisSentinelConnector
{
    public function __construct(protected string $version)
    {
    }

    protected function phpRedisVersion()
    {
        return $this->version;
    }

    public function testSentinelParameters(array $config)
    {
        return $this->sentinelParameters($config);
    }
}
