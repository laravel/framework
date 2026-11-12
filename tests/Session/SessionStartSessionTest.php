<?php

namespace Illuminate\Tests\Session;

use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Session\SessionManager;
use JMac\Testing\Double;
use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;
use PHPUnit\Framework\TestCase;

class SessionStartSessionTest extends TestCase
{
    use VerifiesDoubles;

    public function testPreviousUrlAndRouteAreStoredOnAnySessionImplementation()
    {
        $request = Request::create('http://localhost/home', 'GET');
        $request->setRouteResolver(fn () => (new Route('GET', 'home', fn () => null))->name('home'));

        $session = Double::for(Session::class);
        $session->expects('setPreviousUrl')->with('http://localhost/home');
        $session->expects('setPreviousRoute')->with('home');

        $middleware = new class(Double::for(SessionManager::class)) extends StartSession
        {
            public function storeUrl($request, $session)
            {
                $this->storeCurrentUrl($request, $session);
            }
        };

        $middleware->storeUrl($request, $session);
    }
}
