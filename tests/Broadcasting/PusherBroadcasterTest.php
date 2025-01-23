<?php

namespace Illuminate\Tests\Broadcasting;

use Illuminate\Broadcasting\Broadcasters\PusherBroadcaster;
use Illuminate\Http\Request;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class PusherBroadcasterTest extends TestCase
{
    /**
     * @var \Illuminate\Broadcasting\Broadcasters\PusherBroadcaster
     */
    public $broadcaster;

    public $pusher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pusher = m::mock('Pusher\Pusher');
        $this->broadcaster = m::mock(PusherBroadcaster::class, [$this->pusher])->makePartial();
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

    public function testValidAuthenticationResponseCallPusherSocketAuthMethodWithPrivateChannel()
    {
        $request = $this->getMockRequestWithUserForChannel('private-test');

        $data = [
            'auth' => 'abcd:efgh',
        ];

        $this->pusher->shouldReceive('socket_auth')
            ->once()
            ->andReturn(json_encode($data));

        $this->assertEquals(
            $data,
            $this->broadcaster->validAuthenticationResponse($request, true)
        );
    }

    public function testValidAuthenticationResponseCallPusherPresenceAuthMethodWithPresenceChannel()
    {
        $request = $this->getMockRequestWithUserForChannel('presence-test');

        $data = [
            'auth' => 'abcd:efgh',
            'channel_data' => [
                'user_id' => 42,
                'user_info' => [1, 2, 3, 4],
            ],
        ];

        $this->pusher->shouldReceive('presence_auth')
            ->once()
            ->andReturn(json_encode($data));

        $this->assertEquals(
            $data,
            $this->broadcaster->validAuthenticationResponse($request, true)
        );
    }

    public function testUserAuthenticationForPusher()
    {
        $this->pusher
            ->shouldReceive('getSettings')
            ->andReturn([
                'auth_key' => '278d425bdf160c739803',
                'secret' => '7ad3773142a6692b25b8',
            ]);

        $this->broadcaster = new PusherBroadcaster($this->pusher);

        $this->broadcaster->resolveAuthenticatedUserUsing(function () {
            return ['id' => '12345'];
        });

        $response = $this->broadcaster->resolveAuthenticatedUser(new Request(['socket_id' => '1234.1234']));

        // The result is hard-coded from the Pusher docs
        // See: https://pusher.com/docs/channels/library_auth_reference/auth-signatures/#user-authentication
        $this->assertSame([
            'auth' => '278d425bdf160c739803:4708d583dada6a56435fb8bc611c77c359a31eebde13337c16ab43aa6de336ba',
            'user_data' => json_encode(['id' => '12345']),
        ], $response);
    }

    /**
     * @param  string  $channel
     * @return \Illuminate\Http\Request
     */
    protected function getMockRequestWithUserForChannel($channel)
    {
        $request = m::mock(Request::class);
        $request->shouldReceive('all')->andReturn(['channel_name' => $channel, 'socket_id' => 'abcd.1234']);

        $request->shouldReceive('input')
            ->with('callback', false)
            ->andReturn(false);

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
