<?php

namespace Illuminate\Tests\Broadcasting;

use Illuminate\Broadcasting\Broadcasters\AblyBroadcaster;
use Illuminate\Http\Request;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AblyBroadcasterTest extends TestCase
{
    /**
     * @var \Illuminate\Broadcasting\Broadcasters\AblyBroadcaster
     */
    public $broadcaster;

    public $ably;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ably = m::mock('Ably\AblyRest');
        $this->ably->options = (object) ['key' => 'abcd:efgh'];

        $this->broadcaster = m::mock(AblyBroadcaster::class, [$this->ably])->makePartial();
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

    /**
     * @param  string  $channel
     * @return \Illuminate\Http\Request
     */
    protected function getMockRequestWithUserForChannel($channel)
    {
        $request = m::mock(Request::class);
        $request->channel_name = $channel;
        $request->socket_id = 'abcd.1234';

        $request->shouldReceive('input')
                ->with('callback', false)
                ->andReturn(false);

        $user = m::mock('User');
        $user->shouldReceive('getAuthIdentifierForBroadcasting')
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
        $request->channel_name = $channel;

        $request->shouldReceive('user')
                ->andReturn(null);

        return $request;
    }
}
