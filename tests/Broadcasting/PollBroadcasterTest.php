<?php

namespace Illuminate\Tests\Broadcasting;

use Illuminate\Broadcasting\Broadcasters\PollBroadcaster;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class PollBroadcasterTest extends TestCase
{
    /**
     * @var \Illuminate\Broadcasting\Broadcasters\PollBroadcaster
     */
    public $broadcaster;

    /**
     * @var \Illuminate\Cache\Repository
     */
    public $cache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = new CacheRepository(new ArrayStore);
        $this->broadcaster = m::mock(PollBroadcaster::class, [$this->cache, 60, 'poll_broadcast:', 30, [2, 100]])->makePartial();

        Container::setInstance(new Container);
    }

    protected function tearDown(): void
    {
        m::close();

        parent::tearDown();
    }

    public function testAuthCallValidAuthenticationResponseWithPrivateChannelWhenCallbackReturnTrue()
    {
        $this->broadcaster->channel('test', function () {
            return true;
        });

        $this->broadcaster->shouldReceive('validAuthenticationResponse')
            ->once();

        $this->broadcaster->auth(
            $this->getMockRequestWithUserForChannel('private-test')
        );
    }

    public function testAuthThrowAccessDeniedHttpExceptionWithPrivateChannelWhenCallbackReturnFalse()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->broadcaster->channel('test', function () {
            return false;
        });

        $this->broadcaster->auth(
            $this->getMockRequestWithUserForChannel('private-test')
        );
    }

    public function testAuthThrowAccessDeniedHttpExceptionWithPrivateChannelWhenRequestUserNotFound()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->broadcaster->channel('test', function () {
            return true;
        });

        $this->broadcaster->auth(
            $this->getMockRequestWithoutUserForChannel('private-test')
        );
    }

    public function testAuthCallValidAuthenticationResponseWithPresenceChannelWhenCallbackReturnAnArray()
    {
        $this->broadcaster->channel('test', function () {
            return [1, 2, 3, 4];
        });

        $this->broadcaster->shouldReceive('validAuthenticationResponse')
            ->once();

        $this->broadcaster->auth(
            $this->getMockRequestWithUserForChannel('presence-test')
        );
    }

    public function testAuthThrowAccessDeniedHttpExceptionWithPresenceChannelWhenCallbackReturnNull()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->broadcaster->channel('test', function () {
            //
        });

        $this->broadcaster->auth(
            $this->getMockRequestWithUserForChannel('presence-test')
        );
    }

    public function testAuthThrowAccessDeniedHttpExceptionWithPresenceChannelWhenRequestUserNotFound()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->broadcaster->channel('test', function () {
            return [1, 2, 3, 4];
        });

        $this->broadcaster->auth(
            $this->getMockRequestWithoutUserForChannel('presence-test')
        );
    }

    public function testValidAuthenticationResponseWithPrivateChannel()
    {
        $request = $this->getMockRequestWithUserForChannel('private-test');

        $this->assertEquals(
            json_encode(true),
            $this->broadcaster->validAuthenticationResponse($request, true)
        );
    }

    public function testValidAuthenticationResponseWithPresenceChannel()
    {
        $request = $this->getMockRequestWithUserForChannel('presence-test');

        $this->assertEquals(
            json_encode([
                'channel_data' => [
                    'user_id' => 42,
                    'user_info' => [
                        'a' => 'b',
                        'c' => 'd',
                    ],
                ],
            ]),
            $this->broadcaster->validAuthenticationResponse($request, [
                'a' => 'b',
                'c' => 'd',
            ])
        );
    }

    public function testBroadcastStoresEventInCache()
    {
        $broadcaster = new PollBroadcaster($this->cache, 60, 'poll_broadcast:', 30, [0, 100]);

        $broadcaster->broadcast(['test-channel'], 'TestEvent', ['message' => 'hello']);

        $ids = $this->cache->get('poll_broadcast:test-channel', []);

        $this->assertCount(1, $ids);

        $event = $this->cache->get('poll_broadcast:event:'.$ids[0]);

        $this->assertNotNull($event);
        $this->assertEquals('TestEvent', $event['event']);
        $this->assertEquals(['message' => 'hello'], $event['data']);
        $this->assertNull($event['socket']);
        $this->assertArrayHasKey('timestamp', $event);
        $this->assertArrayHasKey('id', $event);
    }

    public function testBroadcastStoresSocketIdFromPayload()
    {
        $broadcaster = new PollBroadcaster($this->cache, 60, 'poll_broadcast:', 30, [0, 100]);

        $broadcaster->broadcast(['test-channel'], 'TestEvent', [
            'message' => 'hello',
            'socket' => '123.456',
        ]);

        $ids = $this->cache->get('poll_broadcast:test-channel', []);
        $event = $this->cache->get('poll_broadcast:event:'.$ids[0]);

        $this->assertEquals('123.456', $event['socket']);
        $this->assertArrayNotHasKey('socket', $event['data']);
    }

    public function testBroadcastToMultipleChannels()
    {
        $broadcaster = new PollBroadcaster($this->cache, 60, 'poll_broadcast:', 30, [0, 100]);

        $broadcaster->broadcast(['channel-a', 'channel-b'], 'TestEvent', ['data' => 1]);

        $idsA = $this->cache->get('poll_broadcast:channel-a', []);
        $idsB = $this->cache->get('poll_broadcast:channel-b', []);

        $this->assertCount(1, $idsA);
        $this->assertCount(1, $idsB);
        $this->assertEquals($idsA[0], $idsB[0]);
    }

    public function testBroadcastWithEmptyChannelsDoesNothing()
    {
        $broadcaster = new PollBroadcaster($this->cache, 60, 'poll_broadcast:', 30, [0, 100]);

        $broadcaster->broadcast([], 'TestEvent', ['data' => 1]);

        $this->assertNull($this->cache->get('poll_broadcast:test-channel'));
    }

    public function testGetEventsConnectHandshakeReturnsEmptyEventsAndCursor()
    {
        $broadcaster = new PollBroadcaster($this->cache, 60, 'poll_broadcast:', 30, [0, 100]);

        $result = $broadcaster->getEvents(['test-channel']);

        $this->assertEmpty($result['events']);
        $this->assertNotEmpty($result['lastEventId']);
        $this->assertTrue(Str::isUlid($result['lastEventId']));
    }

    public function testGetEventsReturnsEventsAfterLastEventId()
    {
        $broadcaster = new PollBroadcaster($this->cache, 60, 'poll_broadcast:', 30, [0, 100]);

        $connectResult = $broadcaster->getEvents(['test-channel']);
        $lastEventId = $connectResult['lastEventId'];

        $broadcaster->broadcast(['test-channel'], 'Event1', ['msg' => '1']);
        $broadcaster->broadcast(['test-channel'], 'Event2', ['msg' => '2']);

        $result = $broadcaster->getEvents(['test-channel'], $lastEventId);

        $this->assertCount(2, $result['events']);
        $this->assertEquals('Event1', $result['events'][0]['event']);
        $this->assertEquals('Event2', $result['events'][1]['event']);
    }

    public function testGetEventsOnlyReturnsEventsAfterCursor()
    {
        $broadcaster = new PollBroadcaster($this->cache, 60, 'poll_broadcast:', 30, [0, 100]);

        $broadcaster->broadcast(['test-channel'], 'Event1', ['msg' => '1']);

        $connectResult = $broadcaster->getEvents(['test-channel']);
        $lastEventId = $connectResult['lastEventId'];

        $broadcaster->broadcast(['test-channel'], 'Event2', ['msg' => '2']);

        $result = $broadcaster->getEvents(['test-channel'], $lastEventId);

        $this->assertCount(1, $result['events']);
        $this->assertEquals('Event2', $result['events'][0]['event']);
    }

    public function testGetEventsFromMultipleChannels()
    {
        $broadcaster = new PollBroadcaster($this->cache, 60, 'poll_broadcast:', 30, [0, 100]);

        $connectResult = $broadcaster->getEvents(['channel-a', 'channel-b']);
        $lastEventId = $connectResult['lastEventId'];

        $broadcaster->broadcast(['channel-a'], 'EventA', ['source' => 'a']);
        $broadcaster->broadcast(['channel-b'], 'EventB', ['source' => 'b']);

        $result = $broadcaster->getEvents(['channel-a', 'channel-b'], $lastEventId);

        $this->assertCount(2, $result['events']);

        $events = array_column($result['events'], 'event');
        $this->assertContains('EventA', $events);
        $this->assertContains('EventB', $events);
    }

    public function testGetEventsReturnsSortedByUlid()
    {
        $broadcaster = new PollBroadcaster($this->cache, 60, 'poll_broadcast:', 30, [0, 100]);

        $connectResult = $broadcaster->getEvents(['test-channel']);
        $lastEventId = $connectResult['lastEventId'];

        $broadcaster->broadcast(['test-channel'], 'Event1', []);
        $broadcaster->broadcast(['test-channel'], 'Event2', []);
        $broadcaster->broadcast(['test-channel'], 'Event3', []);

        $result = $broadcaster->getEvents(['test-channel'], $lastEventId);

        $this->assertCount(3, $result['events']);
        $this->assertTrue(strcmp($result['events'][0]['id'], $result['events'][1]['id']) < 0);
        $this->assertTrue(strcmp($result['events'][1]['id'], $result['events'][2]['id']) < 0);
    }

    public function testGetEventsUpdatesLastEventId()
    {
        $broadcaster = new PollBroadcaster($this->cache, 60, 'poll_broadcast:', 30, [0, 100]);

        $connectResult = $broadcaster->getEvents(['test-channel']);
        $lastEventId = $connectResult['lastEventId'];

        $broadcaster->broadcast(['test-channel'], 'Event1', []);

        $result = $broadcaster->getEvents(['test-channel'], $lastEventId);
        $newLastEventId = $result['lastEventId'];

        $this->assertNotEquals($lastEventId, $newLastEventId);
        $this->assertEquals($result['events'][0]['id'], $newLastEventId);
    }

    public function testGetEventsKeepsLastEventIdWhenNoNewEvents()
    {
        $broadcaster = new PollBroadcaster($this->cache, 60, 'poll_broadcast:', 30, [0, 100]);

        $connectResult = $broadcaster->getEvents(['test-channel']);
        $lastEventId = $connectResult['lastEventId'];

        $result = $broadcaster->getEvents(['test-channel'], $lastEventId);

        $this->assertEmpty($result['events']);
        $this->assertEquals($lastEventId, $result['lastEventId']);
    }

    public function testGetEventsSkipsExpiredEventPayloads()
    {
        $broadcaster = new PollBroadcaster($this->cache, 60, 'poll_broadcast:', 30, [0, 100]);

        $connectResult = $broadcaster->getEvents(['test-channel']);
        $lastEventId = $connectResult['lastEventId'];

        $broadcaster->broadcast(['test-channel'], 'Event1', []);

        $ids = $this->cache->get('poll_broadcast:test-channel', []);
        $this->cache->forget('poll_broadcast:event:'.$ids[0]);

        $broadcaster->broadcast(['test-channel'], 'Event2', []);

        $result = $broadcaster->getEvents(['test-channel'], $lastEventId);

        $this->assertCount(1, $result['events']);
        $this->assertEquals('Event2', $result['events'][0]['event']);
    }

    public function testGetEventsIncludesChannelInEvent()
    {
        $broadcaster = new PollBroadcaster($this->cache, 60, 'poll_broadcast:', 30, [0, 100]);

        $connectResult = $broadcaster->getEvents(['my-channel']);
        $lastEventId = $connectResult['lastEventId'];

        $broadcaster->broadcast(['my-channel'], 'TestEvent', []);

        $result = $broadcaster->getEvents(['my-channel'], $lastEventId);

        $this->assertEquals('my-channel', $result['events'][0]['channel']);
    }

    public function testUpdatePresenceRecordsUser()
    {
        $broadcaster = new PollBroadcaster($this->cache, 60, 'poll_broadcast:', 30, [0, 100]);

        $result = $broadcaster->updatePresence('presence-chat', 1, ['name' => 'Alice']);

        $this->assertCount(1, $result['members']);
        $this->assertEquals(1, $result['members'][0]['user_id']);
        $this->assertEquals(['name' => 'Alice'], $result['members'][0]['user_info']);
    }

    public function testUpdatePresenceDetectsJoining()
    {
        $broadcaster = new PollBroadcaster($this->cache, 60, 'poll_broadcast:', 30, [0, 100]);

        $result = $broadcaster->updatePresence('presence-chat', 1, ['name' => 'Alice']);

        $this->assertCount(1, $result['joined']);
        $this->assertEquals(1, $result['joined'][0]['user_id']);
    }

    public function testUpdatePresenceDetectsLeaving()
    {
        $broadcaster = new PollBroadcaster($this->cache, 60, 'poll_broadcast:', 1, [0, 100]);

        $broadcaster->updatePresence('presence-chat', 1, ['name' => 'Alice']);

        sleep(2);

        $result = $broadcaster->updatePresence('presence-chat', 2, ['name' => 'Bob']);

        $this->assertCount(1, $result['members']);
        $this->assertEquals(2, $result['members'][0]['user_id']);
        $this->assertCount(1, $result['left']);
        $this->assertEquals(1, $result['left'][0]['user_id']);
    }

    public function testUpdatePresenceSameUserAppearsOnce()
    {
        $broadcaster = new PollBroadcaster($this->cache, 60, 'poll_broadcast:', 30, [0, 100]);

        $broadcaster->updatePresence('presence-chat', 1, ['name' => 'Alice']);
        $result = $broadcaster->updatePresence('presence-chat', 1, ['name' => 'Alice Updated']);

        $this->assertCount(1, $result['members']);
        $this->assertEquals(1, $result['members'][0]['user_id']);
        $this->assertEquals(['name' => 'Alice Updated'], $result['members'][0]['user_info']);
    }

    public function testUpdatePresenceMultipleUsersTracked()
    {
        $broadcaster = new PollBroadcaster($this->cache, 60, 'poll_broadcast:', 30, [0, 100]);

        $broadcaster->updatePresence('presence-chat', 1, ['name' => 'Alice']);
        $result = $broadcaster->updatePresence('presence-chat', 2, ['name' => 'Bob']);

        $this->assertCount(2, $result['members']);
    }

    public function testChannelKeyGeneration()
    {
        $broadcaster = new PollBroadcaster($this->cache, 60, 'poll_broadcast:', 30, [0, 100]);

        $this->assertEquals('poll_broadcast:test-channel', $broadcaster->channelKey('test-channel'));
    }

    public function testEventKeyGeneration()
    {
        $broadcaster = new PollBroadcaster($this->cache, 60, 'poll_broadcast:', 30, [0, 100]);

        $this->assertEquals('poll_broadcast:event:01ABC', $broadcaster->eventKey('01ABC'));
    }

    public function testPresenceKeyGeneration()
    {
        $broadcaster = new PollBroadcaster($this->cache, 60, 'poll_broadcast:', 30, [0, 100]);

        $this->assertEquals('poll_broadcast:presence:presence-chat', $broadcaster->presenceKey('presence-chat'));
    }

    /**
     * @param  string  $channel
     * @return \Illuminate\Http\Request
     */
    protected function getMockRequestWithUserForChannel($channel)
    {
        $request = m::mock(Request::class);
        $request->shouldReceive('all')->andReturn(['channel_name' => $channel]);

        $user = m::mock('User');
        $user->shouldReceive('getAuthIdentifierForBroadcasting')
            ->andReturn(42);
        $user->shouldReceive('getAuthIdentifier')
            ->andReturn(42);

        $request->shouldReceive('user')
            ->andReturn($user);

        $request->channel_name = $channel;

        return $request;
    }

    /**
     * @param  string  $channel
     * @return \Illuminate\Http\Request
     */
    protected function getMockRequestWithoutUserForChannel($channel)
    {
        $request = m::mock(Request::class);
        $request->shouldReceive('all')->andReturn(['channel_name' => $channel]);

        $request->shouldReceive('user')
            ->andReturn(null);

        $request->channel_name = $channel;

        return $request;
    }
}
