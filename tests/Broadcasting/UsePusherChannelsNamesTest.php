<?php

namespace Illuminate\Tests\Broadcasting;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Illuminate\Broadcasting\Broadcasters\UsePusherChannelConventions;

class UsePusherChannelConventionsTest extends TestCase
{
    /**
     * @var \Illuminate\Broadcasting\Broadcasters\RedisBroadcaster
     */
    public $broadcaster;

    public function setUp()
    {
        parent::setUp();

        $this->broadcaster = new FakeBroadcasterUsingPusherChannelsNames();
    }

    public function tearDown()
    {
        m::close();
    }

    /**
     * @dataProvider channelsProvider
     */
    public function testChannelNameNormalization($requestChannelName, $normalizedName)
    {
        $this->assertEquals(
            $normalizedName,
            $this->broadcaster->normalizeChannelName($requestChannelName)
        );
    }

    /**
     * @dataProvider channelsProvider
     */
    public function testIsGuardedChannel($requestChannelName, $_, $guarded)
    {
        $this->assertEquals(
            $guarded,
            $this->broadcaster->isGuardedChannel($requestChannelName)
        );
    }

    public function channelsProvider()
    {
        $prefixesInfos = [
            ['prefix' => 'private-', 'guarded' => true],
            ['prefix' => 'presence-', 'guarded' => true],
            ['prefix' => '', 'guarded' => false],
        ];

        $channels = [
            'test',
            'test-channel',
            'test-private-channel',
            'test-presence-channel',
            'abcd.efgh',
            'abcd.efgh.ijkl',
            'test.{param}',
            'test-{param}',
            '{a}.{b}',
            '{a}-{b}',
            '{a}-{b}.{c}',
        ];

        $tests = [];
        foreach ($prefixesInfos as $prefixInfos) {
            foreach ($channels as $channel) {
                $tests[] = [
                    $prefixInfos['prefix'].$channel,
                    $channel,
                    $prefixInfos['guarded'],
                ];
            }
        }

        $tests[] = ['private-private-test', 'private-test', true];
        $tests[] = ['private-presence-test', 'presence-test', true];
        $tests[] = ['presence-private-test', 'private-test', true];
        $tests[] = ['presence-presence-test', 'presence-test', true];
        $tests[] = ['public-test', 'public-test', false];

        return $tests;
    }
}

class FakeBroadcasterUsingPusherChannelsNames extends Broadcaster
{
    use UsePusherChannelConventions;

    public function auth($request)
    {
    }

    public function validAuthenticationResponse($request, $result)
    {
    }

    public function broadcast(array $channels, $event, array $payload = [])
    {
    }
}
