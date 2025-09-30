<?php

namespace Illuminate\Tests\Redis\Connections;

use Illuminate\Redis\Connections\PhpRedisConnection;
use PHPUnit\Framework\TestCase;

class PhpRedisConnectionTest extends TestCase
{
    public function test_applies_prefix_to_normal_channel()
    {
        $mockClient = $this->createMock(\Redis::class);
        $mockClient->method('getOption')
            ->with(\Redis::OPT_PREFIX)
            ->willReturn('myprefix:');

        $conn = new PhpRedisConnection($mockClient, null, ['options' => []]);

        $method = new \ReflectionMethod($conn, 'channelsWithAppliedPrefix');
        $method->setAccessible(true);

        $this->assertSame(['myprefix:orders'], $method->invoke($conn, ['orders']));
    }

    public function test_skips_prefix_for_keyevent_channel()
    {
        $mockClient = $this->createMock(\Redis::class);
        $mockClient->method('getOption')
            ->with(\Redis::OPT_PREFIX)
            ->willReturn('myprefix:');

        $conn = new PhpRedisConnection($mockClient, null, ['options' => []]);

        $method = new \ReflectionMethod($conn, 'channelsWithAppliedPrefix');
        $method->setAccessible(true);

        $this->assertSame(['__keyevent@0__:expired'], $method->invoke($conn, ['__keyevent@0__:expired']));
    }
}
