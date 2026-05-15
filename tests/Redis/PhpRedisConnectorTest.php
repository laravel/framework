<?php

namespace Illuminate\Tests\Redis;

use Illuminate\Redis\Connectors\PhpRedisConnector;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

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

    public function testParseBackoffAlgorithmParsesValidNames()
    {
        if (! extension_loaded('redis')) {
            $this->markTestSkipped('Requires phpredis extension.');
        }

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
}
