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
        $this->ably->shouldReceive('time')
            ->andReturn(time());

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
            $this->getMockRequestWithUserForChannel('private:test', null)
        );
    }

    public function testAuthThrowAccessDeniedHttpExceptionWithPrivateChannelWhenCallbackReturnFalse()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->broadcaster->channel('test', function () {
            return false;
        });

        $this->broadcaster->auth(
            $this->getMockRequestWithUserForChannel('private:test', null)
        );
    }

    public function testAuthThrowAccessDeniedHttpExceptionWithPrivateChannelWhenRequestUserNotFound()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->broadcaster->channel('test', function () {
            return true;
        });

        $this->broadcaster->auth(
            $this->getMockRequestWithoutUserForChannel('private:test', null)
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
            $this->getMockRequestWithUserForChannel('presence:test', null)
        );
    }

    public function testAuthThrowAccessDeniedHttpExceptionWithPresenceChannelWhenCallbackReturnNull()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->broadcaster->channel('test', function () {
            //
        });

        $this->broadcaster->auth(
            $this->getMockRequestWithUserForChannel('presence:test', null)
        );
    }

    public function testAuthThrowAccessDeniedHttpExceptionWithPresenceChannelWhenRequestUserNotFound()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->broadcaster->channel('test', function () {
            return [1, 2, 3, 4];
        });

        $this->broadcaster->auth(
            $this->getMockRequestWithoutUserForChannel('private:test', null)
        );
    }

    public function testGenerateAndValidateToken() {
        $headers = array('alg'=>'HS256','typ'=>'JWT');
        $payload = array('sub'=>'1234567890','name'=>'John Doe', 'admin'=>true, 'exp'=>(time() + 60));
        $jwtToken = AblyBroadcaster::generateJwt($headers, $payload);

        $parsedJwt = AblyBroadcaster::parseJwt($jwtToken);
        self::assertEquals("HS256", $parsedJwt['header']['alg']);
        self::assertEquals("JWT", $parsedJwt['header']['typ']);

        self::assertEquals("1234567890", $parsedJwt['payload']['sub']);
        self::assertEquals("John Doe", $parsedJwt['payload']['name']);
        self::assertEquals(true, $parsedJwt['payload']['admin']);


        $timeFn = function () {return time(); };
        $jwtIsValid = AblyBroadcaster::isJwtValid($jwtToken, $timeFn);
        self::assertTrue($jwtIsValid);
    }

    /**
     * @param  string  $channel
     * @return \Illuminate\Http\Request
     */
    protected function getMockRequestWithUserForChannel($channel, $token)
    {
        $request = m::mock(Request::class);
        $request->channel_name = $channel;
        $request->token = $token;
        $request->socket_id = 'abcd.1234';

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
    protected function getMockRequestWithoutUserForChannel($channel, $token)
    {
        $request = m::mock(Request::class);
        $request->channel_name = $channel;
        $request->token = $token;
        $request->socket_id = 'abcd.1234';


        $request->shouldReceive('user')
            ->andReturn(null);

        return $request;
    }
}
