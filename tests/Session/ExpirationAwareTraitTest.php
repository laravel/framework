<?php

class ExpirationAwareTraitTest extends PHPUnit_Framework_TestCase {
	
	public function testSetLifetime()
	{
		$handler = new Illuminate\Session\CookieSessionHandler(new \Illuminate\Cookie\CookieJar);
		$this->assertNull($handler->getLifetime());

		$handler->setLifetime(100);
		$this->assertEquals(100, $handler->getLifetime());

		$handler->setLifetime(120);
		$this->assertEquals(120, $handler->getLifetime());
	}

	public function testSetLifetimeString()
	{
		$handler = new Illuminate\Session\CookieSessionHandler(new \Illuminate\Cookie\CookieJar);
		$this->assertNull($handler->getLifetime());

		$this->setExpectedException('InvalidArgumentException');
		$handler->setLifetime('asdf');
		$this->assertEquals(120, $handler->getLifetime());
	}

	public function testSetLifetimeNegative()
	{
		$handler = new Illuminate\Session\CookieSessionHandler(new \Illuminate\Cookie\CookieJar);

		$this->setExpectedException('InvalidArgumentException');
		$handler->setLifetime(-1);
		$this->assertEquals(120, $handler->getLifetime());
	}

}
