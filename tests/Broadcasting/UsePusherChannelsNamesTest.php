<?php

namespace Illuminate\Tests\Broadcasting;

use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Illuminate\Broadcasting\Broadcasters\UsePusherChannelConventions;
use PHPUnit\Framework\TestCase;

class UsePusherChannelConventionsTest extends TestCase
{
    /**
     * @dataProvider channelsProvider
     */
    public function testChannelNameNormalization($requestChannelName, $normalizedName)
    {
        $broadcaster = new FakeBroadcasterUsingPusherChannelsNames();

        $this->assertSame(
            $normalizedName,
            $broadcaster->normalizeChannelName($requestChannelName)
        );
    }

    public function testChannelNameNormalizationSpecialCase()
    {
        $broadcaster = new FakeBroadcasterUsingPusherChannelsNames();

        $this->assertSame(
            'private-123',
            $broadcaster->normalizeChannelName('private-encrypted-private-123')
        );
    }

    /**
     * @dataProvider channelsProvider
     */
    public function testIsGuardedChannel($requestChannelName, $_, $guarded)
    {
        $broadcaster = new FakeBroadcasterUsingPusherChannelsNames();

        $this->assertSame(
            $guarded,
            $broadcaster->isGuardedChannel($requestChannelName)
        );
    }

    /**
     * @return \Generator
     */
    public function channelsProvider()
    {
        $prefixesInfos = [
            ['prefix' => 'private-', 'guarded' => true],
            ['prefix' => 'private-encrypted-', 'guarded' => true],
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

        foreach ($prefixesInfos as $prefixInfos) {
            foreach ($channels as $channel) {
                yield [
                    $prefixInfos['prefix'].$channel,
                    $channel,
                    $prefixInfos['guarded'],
                ];
            }
        }

        yield ['private-private-test', 'private-test', true];
        yield ['private-presence-test', 'presence-test', true];
        yield ['presence-private-test', 'private-test', true];
        yield ['presence-presence-test', 'presence-test', true];
        yield ['public-test', 'public-test', false];
    }
}

class FakeBroadcasterUsingPusherChannelsNames extends Broadcaster
{
    use UsePusherChannelConventions;

    public function auth($request)
    {
        //
    }

    public function validAuthenticationResponse($request, $result)
    {
        //
    }

    public function broadcast(array $channels, $event, array $payload = [])
    {
        //
    }
}
