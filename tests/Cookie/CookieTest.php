<?php

use Mockery as m;
use Illuminate\Cookie\CookieJar;
use Illuminate\Encryption\Encrypter;
use Symfony\Component\HttpFoundation\Request;

class CookieTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testCookiesAreCreatedWithProperOptions()
	{
		$cookie = $this->getCreator();
		$cookie->setDefaultPathAndDomain('foo', 'bar');
		$c = $cookie->make('color', 'blue', 10, '/path', '/domain', true, false);
		$value = $cookie->getEncrypter()->decrypt($c->getValue());
		$this->assertEquals('blue', $value);
		$this->assertFalse($c->isHttpOnly());
		$this->assertTrue($c->isSecure());
		$this->assertEquals('/domain', $c->getDomain());
		$this->assertEquals('/path', $c->getPath());

		$c2 = $cookie->forever('color', 'blue', '/path', '/domain', true, false);
		$value = $cookie->getEncrypter()->decrypt($c2->getValue());
		$this->assertEquals('blue', $value);
		$this->assertFalse($c2->isHttpOnly());
		$this->assertTrue($c2->isSecure());
		$this->assertEquals('/domain', $c2->getDomain());
		$this->assertEquals('/path', $c2->getPath());
	}


	public function testCookiesAreCreatedWithProperOptionsUsingDefaultPathAndDomain()
	{
		$cookie = $this->getCreator();
		$cookie->setDefaultPathAndDomain('/path', '/domain');
		$c = $cookie->make('color', 'blue', 10, null, null, true, false);
		$value = $cookie->getEncrypter()->decrypt($c->getValue());
		$this->assertEquals('blue', $value);
		$this->assertFalse($c->isHttpOnly());
		$this->assertTrue($c->isSecure());
		$this->assertEquals('/domain', $c->getDomain());
		$this->assertEquals('/path', $c->getPath());
	}


	public function testCookiesAreProperlyParsed()
	{
		$cookie = $this->getCreator();
		$value = $cookie->getEncrypter()->encrypt('bar');
		$cookie->getRequest()->cookies->set('foo', $value);
		$this->assertEquals('bar', $cookie->get('foo'));
        $this->assertEquals('zee', $cookie->get('someOtherFoo', 'zee'));
        
		$cookie = $this->getCreator();
		$value = $cookie->getEncrypter()->encrypt('bar');
		$value = str_shuffle($value);
		$cookie->getRequest()->cookies->set('foo', $value);
		$this->assertNull($cookie->get('foo'));
	}

	public function testQueuedCookies()
	{
		$cookie = $this->getCreator();
		$this->assertEmpty($cookie->getQueuedCookies()); // better not be anything in the array yet
		$cookie->queue($cookie->make('foo','bar'));
		$this->assertArrayHasKey('foo',$cookie->getQueuedCookies());
	}

	public function testUnqueue()
	{
		$cookie = $this->getCreator();
		$cookie->queue($cookie->make('foo','bar'));
		$this->assertArrayHasKey('foo',$cookie->getQueuedCookies());
		$cookie->unqueue('foo');
		$this->assertEmpty($cookie->getQueuedCookies());
	}

	public function getCreator()
	{
		return new CookieJar(Request::create('/foo', 'GET'), new Encrypter(str_repeat('a', 16)), array(
			'path'     => '/path',
			'domain'   => '/domain',
			'secure'   => true,
			'httpOnly' => false,
		));
	}

}
