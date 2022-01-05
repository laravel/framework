<?php

namespace Illuminate\Tests\Redis;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\Concerns\InteractsWithRedis;
use Illuminate\Redis\RedisManager;
use PHPUnit\Framework\TestCase;

class RedisConnectorTest extends TestCase
{
    use InteractsWithRedis;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRedis();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->tearDownRedis();
    }

    public function testDefaultConfiguration()
    {
        $host = env('REDIS_HOST', '127.0.0.1');
        $port = env('REDIS_PORT', 6379);

        $predisClient = $this->redis['predis']->connection()->client();
        $parameters = $predisClient->getConnection()->getParameters();
        $this->assertSame('tcp', $parameters->scheme);
        $this->assertEquals($host, $parameters->host);
        $this->assertEquals($port, $parameters->port);

        $phpRedisClient = $this->redis['phpredis']->connection()->client();
        $this->assertEquals($host, $phpRedisClient->getHost());
        $this->assertEquals($port, $phpRedisClient->getPort());
        $this->assertSame('default', $phpRedisClient->client('GETNAME'));
    }

    public function testUrl()
    {
        $host = env('REDIS_HOST', '127.0.0.1');
        $port = env('REDIS_PORT', 6379);

        $predis = new RedisManager(new Application, 'predis', [
            'cluster' => false,
            'options' => [
                'prefix' => 'test_',
            ],
            'default' => [
                'url' => "redis://{$host}:{$port}",
                'database' => 5,
                'timeout' => 0.5,
            ],
        ]);
        $predisClient = $predis->connection()->client();
        $parameters = $predisClient->getConnection()->getParameters();
        $this->assertSame('tcp', $parameters->scheme);
        $this->assertEquals($host, $parameters->host);
        $this->assertEquals($port, $parameters->port);

        $phpRedis = new RedisManager(new Application, 'phpredis', [
            'cluster' => false,
            'options' => [
                'prefix' => 'test_',
            ],
            'default' => [
                'url' => "redis://{$host}:{$port}",
                'database' => 5,
                'timeout' => 0.5,
            ],
        ]);
        $phpRedisClient = $phpRedis->connection()->client();
        $this->assertSame("tcp://{$host}", $phpRedisClient->getHost());
        $this->assertEquals($port, $phpRedisClient->getPort());
    }

    public function testUrlWithScheme()
    {
        $host = env('REDIS_HOST', '127.0.0.1');
        $port = env('REDIS_PORT', 6379);

        $predis = new RedisManager(new Application, 'predis', [
            'cluster' => false,
            'options' => [
                'prefix' => 'test_',
            ],
            'default' => [
                'url' => "tls://{$host}:{$port}",
                'database' => 5,
                'timeout' => 0.5,
            ],
        ]);
        $predisClient = $predis->connection()->client();
        $parameters = $predisClient->getConnection()->getParameters();
        $this->assertSame('tls', $parameters->scheme);
        $this->assertEquals($host, $parameters->host);
        $this->assertEquals($port, $parameters->port);

        $phpRedis = new RedisManager(new Application, 'phpredis', [
            'cluster' => false,
            'options' => [
                'prefix' => 'test_',
            ],
            'default' => [
                'url' => "tcp://{$host}:{$port}",
                'database' => 5,
                'timeout' => 0.5,
            ],
        ]);
        $phpRedisClient = $phpRedis->connection()->client();
        $this->assertSame("tcp://{$host}", $phpRedisClient->getHost());
        $this->assertEquals($port, $phpRedisClient->getPort());
    }

    public function testScheme()
    {
        $host = env('REDIS_HOST', '127.0.0.1');
        $port = env('REDIS_PORT', 6379);

        $predis = new RedisManager(new Application, 'predis', [
            'cluster' => false,
            'options' => [
                'prefix' => 'test_',
            ],
            'default' => [
                'scheme' => 'tls',
                'host' => $host,
                'port' => $port,
                'database' => 5,
                'timeout' => 0.5,
            ],
        ]);
        $predisClient = $predis->connection()->client();
        $parameters = $predisClient->getConnection()->getParameters();
        $this->assertSame('tls', $parameters->scheme);
        $this->assertEquals($host, $parameters->host);
        $this->assertEquals($port, $parameters->port);

        $phpRedis = new RedisManager(new Application, 'phpredis', [
            'cluster' => false,
            'options' => [
                'prefix' => 'test_',
            ],
            'default' => [
                'scheme' => 'tcp',
                'host' => $host,
                'port' => $port,
                'database' => 5,
                'timeout' => 0.5,
            ],
        ]);
        $phpRedisClient = $phpRedis->connection()->client();
        $this->assertSame("tcp://{$host}", $phpRedisClient->getHost());
        $this->assertEquals($port, $phpRedisClient->getPort());
    }

    public function testPredisConfigurationWithUsername()
    {
        $host = env('REDIS_HOST', '127.0.0.1');
        $port = env('REDIS_PORT', 6379);
        $username = 'testuser';
        $password = 'testpw';

        $predis = new RedisManager(new Application, 'predis', [
            'default' => [
                'host' => $host,
                'port' => $port,
                'username' => $username,
                'password' => $password,
                'database' => 5,
                'timeout' => 0.5,
            ],
        ]);
        $predisClient = $predis->connection()->client();
        $parameters = $predisClient->getConnection()->getParameters();
        $this->assertEquals($username, $parameters->username);
        $this->assertEquals($password, $parameters->password);
    }

    public function testPredisConfigurationWithSentinel()
    {
        $host = env('REDIS_HOST', '127.0.0.1');
        $port = env('REDIS_PORT', 6379);

        $predis = new RedisManager(new Application, 'predis', [
            'cluster' => false,
            'options' => [
                'replication' => 'sentinel',
                'service' => 'mymaster',
                'parameters' => [
                    'default' => [
                        'database' => 5,
                    ],
                ],
            ],
            'default' => [
                "tcp://{$host}:{$port}",
            ],
        ]);

        $predisClient = $predis->connection()->client();
        $parameters = $predisClient->getConnection()->getSentinelConnection()->getParameters();
        $this->assertEquals($host, $parameters->host);
    }
}
