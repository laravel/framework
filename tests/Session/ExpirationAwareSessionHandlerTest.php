<?php

use Mockery as m;

class ExpirationAwareSessionHandlerTest extends PHPUnit_Framework_TestCase {

    public function tearDown()
    {
        m::close();
    }

    public function testNoDefaultLifetime()
    {
        $handler = m::mock(
            new \Illuminate\Session\CookieSessionHandler(
                new \Illuminate\Cookie\CookieJar()
            )
        );

        $this->assertEquals(0, $handler->getLifetime());
    }

    public function testLifetimeAccessorMutator()
    {
        $handler = m::mock(
            new \Illuminate\Session\CookieSessionHandler(
                new \Illuminate\Cookie\CookieJar()
            )
        );

        $handler->setLifetime(100);
        $this->assertEquals(100, $handler->getLifetime());

        $handler->setLifetime(200);
        $this->assertEquals(200, $handler->getLifetime());
    }
}
