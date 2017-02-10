<?php

use Mockery as m;

class ExpirationAwareSessionHandlerTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}

	public function testLifetimeAccessorMutator()
	{
		$handler = new \Illuminate\Session\CookieSessionHandler(
			new \Illuminate\Cookie\CookieJar(),
			50
		);

		$this->assertEquals(50, $handler->getLifetime());

		$handler->setLifetime(100);
		$this->assertEquals(100, $handler->getLifetime());

		$handler->setLifetime(200);
		$this->assertEquals(200, $handler->getLifetime());
	}
}
