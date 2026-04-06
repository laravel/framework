<?php

namespace Illuminate\Tests\Redis;

use Illuminate\Redis\Connectors\PhpRedisConnector;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class PhpRedisConnectorTest extends TestCase
{
    protected PhpRedisConnector $connector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connector = new PhpRedisConnector;
    }

    public function testNormalizeContextWrapsFlatArrayInStream()
    {
        $result = $this->callNormalizeContext([
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
        $result = $this->callNormalizeContext([
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
        $context = [
            'stream' => [
                'verify_peer' => false,
            ],
        ];

        $result = $this->callNormalizeContext($context);

        $this->assertSame($context, $result);
    }

    public function testNormalizeClusterContextUnwrapsSslKey()
    {
        $result = $this->callNormalizeClusterContext([
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
        $result = $this->callNormalizeClusterContext([
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
        $context = [
            'verify_peer' => false,
            'verify_peer_name' => false,
        ];

        $result = $this->callNormalizeClusterContext($context);

        $this->assertSame($context, $result);
    }

    public function testNormalizeContextSslKeyTakesPrecedenceOverFlatKeys()
    {
        $result = $this->callNormalizeContext([
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

    public function testNormalizeClusterContextSslKeyTakesPrecedenceOverFlatKeys()
    {
        $result = $this->callNormalizeClusterContext([
            'verify_peer' => true,
            'ssl' => [
                'verify_peer' => false,
            ],
        ]);

        $this->assertSame([
            'verify_peer' => false,
        ], $result);
    }

    protected function callNormalizeContext(array $context): array
    {
        $method = new ReflectionMethod(PhpRedisConnector::class, 'normalizeContext');

        return $method->invoke($this->connector, $context);
    }

    protected function callNormalizeClusterContext(array $context): array
    {
        $method = new ReflectionMethod(PhpRedisConnector::class, 'normalizeClusterContext');

        return $method->invoke($this->connector, $context);
    }
}
