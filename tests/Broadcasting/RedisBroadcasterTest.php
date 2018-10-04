<?php

namespace Illuminate\Tests\Broadcasting;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Broadcasting\Broadcasters\RedisBroadcaster;

class RedisBroadcasterTest extends TestCase
{
    /**
     * @var \Illuminate\Broadcasting\Broadcasters\RedisBroadcaster
     */
    public $broadcaster;

    public function setUp()
    {
        parent::setUp();

        $this->broadcaster = m::mock(RedisBroadcaster::class)->makePartial();
    }

    public function tearDown()
    {
        m::close();
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

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function testAuthThrowAccessDeniedHttpExceptionWithPrivateChannelWhenCallbackReturnFalse()
    {
        $this->broadcaster->channel('test', function () {
            return false;
        });

        $this->broadcaster->auth(
            $this->getMockRequestWithUserForChannel('private-test')
        );
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function testAuthThrowAccessDeniedHttpExceptionWithPrivateChannelWhenRequestUserNotFound()
    {
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

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function testAuthThrowAccessDeniedHttpExceptionWithPresenceChannelWhenCallbackReturnNull()
    {
        $this->broadcaster->channel('test', function () {
        });

        $this->broadcaster->auth(
            $this->getMockRequestWithUserForChannel('presence-test')
        );
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function testAuthThrowAccessDeniedHttpExceptionWithPresenceChannelWhenRequestUserNotFound()
    {
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

    /**
     * @param  string  $channel
     * @return \Illuminate\Http\Request
     */
    protected function getMockRequestWithUserForChannel($channel)
    {
        $request = m::mock(\Illuminate\Http\Request::class);
        $request->channel_name = $channel;

        $user = m::mock('User');
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
        $request = m::mock(\Illuminate\Http\Request::class);
        $request->channel_name = $channel;

        $request->shouldReceive('user')
                ->andReturn(null);

        return $request;
    }
}
