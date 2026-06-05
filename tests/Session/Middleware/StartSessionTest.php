<?php

namespace Illuminate\Tests\Session\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Session\SessionManager;
use Illuminate\Contracts\Session\Session;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class StartSessionTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testStartSessionReturnsResponseWhenSessionBlockIsTrueAndRequestHasNoRoute()
    {
        $manager = m::mock(SessionManager::class);
        $session = m::mock(Session::class);

        $manager->shouldReceive('getSessionConfig')->andReturn([
            'driver' => 'file',
            'lottery' => [0, 100],
            'path' => '/',
            'domain' => null,
            'secure' => false,
            'expire_on_close' => false,
            'lifetime' => 120,
        ]);

        $manager->shouldReceive('driver')->andReturn($session);
        $manager->shouldReceive('shouldBlock')->andReturn(true);

        $session->shouldReceive('getName')->andReturn('session_cookie');
        $session->shouldReceive('getId')->andReturn('session_id');
        $session->shouldReceive('setId')->with(null);
        $session->shouldReceive('setRequestOnHandler');
        $session->shouldReceive('start');
        $session->shouldReceive('getHandler')->andReturn(m::mock(\SessionHandlerInterface::class));
        $session->shouldReceive('save');

        $request = Request::create('/', 'GET');
        
        $this->assertNull($request->route());

        $middleware = new StartSession($manager);
        
        $response = new Response('ok');
        
        $result = $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });

        $this->assertSame($response, $result);
    }
}
