<?php

use Mockery as m;

class CookieSessionHandlerTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}

	public function testDefaultLifetime()
	{
		$handler = new \Illuminate\Session\CookieSessionHandler(
			new \Illuminate\Cookie\CookieJar(),
			120
		);

		$this->assertEquals(120, $handler->getLifetime());
	}

}
