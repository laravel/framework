<?php

namespace Illuminate\Tests\Broadcasting;

use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Illuminate\Broadcasting\Broadcasters\UsePusherChannelConventions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UsePusherChannelsNamesTest extends TestCase
{
    #[DataProvider('channelsProvider')]
    public function testChannelNameNormalization($requestChannelName, $normalizedName)
    {
        $broadcaster = new FakeBroadcasterUsingPusherChannelsNames;

        $this->assertSame(
            $normalizedName,
            $broadcaster->normalizeChannelName($requestChannelName)
        );
    }

    public function testChannelNameNormalizationSpecialCase()
    {
        $broadcaster = new FakeBroadcasterUsingPusherChannelsNames;

        $this->assertSame(
            'private-123',
            $broadcaster->normalizeChannelName('private-encrypted-private-123')
        );
    }

    public function testChannelNamePatternMatching()
    {
        $broadcaster = new FakeBroadcasterUsingPusherChannelsNames;

        $this->assertEquals(
            0,
            $broadcaster->testChannelNameMatchesPattern(
                'TestChannel',
                'Test.{id}'
            )
        );
    }

    #[DataProvider('channelsProvider')]
    public function testIsGuardedChannel($requestChannelName, $_, $guarded)
    {
        $broadcaster = new FakeBroadcasterUsingPusherChannelsNames;

        $this->assertSame(
            $guarded,
            $broadcaster->isGuardedChannel($requestChannelName)
        );
    }

    public static function channelsProvider()
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

    public function testChannelNameMatchesPattern($channel, $pattern)
    {
        return $this->channelNameMatchesPattern($channel, $pattern);
    }
}
