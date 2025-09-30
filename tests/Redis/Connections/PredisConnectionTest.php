<?php

namespace Illuminate\Tests\Redis\Connections;

use Illuminate\Redis\Connections\PredisConnection;
use PHPUnit\Framework\TestCase;

class PredisConnectionTest extends TestCase
{
    public function test_applies_prefix_to_normal_channel()
    {
        $mockClient = $this->createMock(\Predis\Client::class);
        $mockClient->method('getOptions')
            ->willReturn((object) ['prefix' => 'myprefix:']);

        $conn = new PredisConnection($mockClient);

        $method = new \ReflectionMethod($conn, 'channelsWithAppliedPrefix');
        $method->setAccessible(true);

        $this->assertSame(['myprefix:orders'], $method->invoke($conn, ['orders']));
    }

    public function test_skips_prefix_for_keyevent_channel()
    {
        $mockClient = $this->createMock(\Predis\Client::class);
        $mockClient->method('getOptions')
            ->willReturn((object) ['prefix' => 'myprefix:']);

        $conn = new PredisConnection($mockClient);

        $method = new \ReflectionMethod($conn, 'channelsWithAppliedPrefix');
        $method->setAccessible(true);

        $this->assertSame(['__keyevent@0__:expired'], $method->invoke($conn, ['__keyevent@0__:expired']));
    }
}
