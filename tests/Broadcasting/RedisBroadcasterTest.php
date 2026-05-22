<?php

namespace Illuminate\Tests\Broadcasting;

use Illuminate\Broadcasting\Broadcasters\RedisBroadcaster;
use Illuminate\Config\Repository as Config;
use Illuminate\Container\Container;
use Illuminate\Contracts\Redis\Factory as Redis;
use Illuminate\Http\Request;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class RedisBroadcasterTest extends TestCase
{
    /**
     * @var \Illuminate\Broadcasting\Broadcasters\RedisBroadcaster
     */
    public $broadcaster;

    protected function setUp(): void
    {
        parent::setUp();

        $this->broadcaster = m::mock(RedisBroadcaster::class)->makePartial();
        $container = Container::setInstance(new Container);

        $container->singleton('config', function () {
            return $this->createConfig();
        });
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
        $returnData = [1, 2, 3, 4];
        $this->broadcaster->channel('test', function () use ($returnData) {
            return $returnData;
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

    public function testBroadcastDoesNotIncludeSocketInPayloadData()
    {
        $redis = m::mock(Redis::class);
        $connection = m::mock('stdClass');

        $redis->shouldReceive('connection')->once()->with(null)->andReturn($connection);

        $connection->shouldReceive('eval')->once()->withArgs(function ($script, $numberOfKeys, $payload, $channel) {
            $payload = json_decode($payload, true);

            $this->assertSame(0, $numberOfKeys);
            $this->assertSame('orders', $channel);
            $this->assertSame('OrderUpdated', $payload['event']);
            $this->assertSame('123.456', $payload['socket']);
            $this->assertSame(['id' => 1], $payload['data']);

            return true;
        });

        (new RedisBroadcaster($redis))->broadcast(['orders'], 'OrderUpdated', [
            'id' => 1,
            'socket' => '123.456',
        ]);
    }

    /**
     * Create a new config repository instance.
     *
     * @return \Illuminate\Config\Repository
     */
    protected function createConfig()
    {
        return new Config([
            'redis' => [
                'options' => ['prefix' => 'laravel_database_'],
            ],
        ]);
    }

    /**
     * @param  string  $channel
     * @return \Illuminate\Http\Request
     */
    protected function getMockRequestWithUserForChannel($channel)
    {
        $request = m::mock(Request::class);
        $request->shouldReceive('all')->andReturn(['channel_name' => $channel]);
        $request->shouldReceive('all')->andReturn(['channel_name' => $channel]);

        $user = m::mock('User');
        $user->shouldReceive('getAuthIdentifierForBroadcasting')
            ->andReturn(42);
        $user->shouldReceive('getAuthIdentifier')
            ->andReturn(42);

        $request->shouldReceive('user')
            ->andReturn($user);

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

        return $request;
    }
}
