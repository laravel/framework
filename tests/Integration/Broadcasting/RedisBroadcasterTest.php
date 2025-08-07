<?php

namespace Illuminate\Tests\Integration\Broadcasting;

use Illuminate\Broadcasting\Broadcasters\RedisBroadcaster;
use Illuminate\Foundation\Testing\Concerns\InteractsWithRedis;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

#[RequiresPhpExtension('redis')]
class RedisBroadcasterTest extends TestCase
{
    use InteractsWithRedis;

    /**
     * @param  string  $driver
     */
    #[DataProvider('redisDriverProvider')]
    #[RequiresPhpExtension('pcntl')]
    public function testBroadcast($driver)
    {
        $this->beforeApplicationDestroyed(function () {
            $this->tearDownRedis();
        });

        if ($pid = pcntl_fork() > 0) {
            $this->setUpRedis();
            /** @var \Redis|\RedisCluster $redisClient */
            $redisClient = $this->redis['phpredis']->client();
            $redisClient->subscribe(['channel-1'], function ($redis, $channel, $message) {
                $redis->unsubscribe(['channel-1']);
                $redis->close();
                $receivedPayload = json_decode($message, true);
                $this->assertEquals('test_channel-1', $channel);
                $this->assertEquals([
                    'event' => 'test.event',
                    'data' => ['foo' => 'bar'],
                    'socket' => null,
                ], $receivedPayload);
            });
        } elseif ($pid == 0) {
            $this->setUpRedis();
            $redis = $this->redis[$driver];
            $broadcaster = new RedisBroadcaster($redis, null, 'test_');
            $channels = ['channel-1', 'channel-2'];
            usleep(1000);
            $broadcaster->broadcast($channels, 'test.event', ['foo' => 'bar']);
            exit;
        } else {
            $this->fail('Cannot fork');
        }
    }
}
