<?php

use Mockery as m;
use Illuminate\CookieJar;
use Illuminate\Encrypter;
use Symfony\Component\HttpFoundation\Request;

class CookieTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testCookiesAreCreatedWithProperOptions()
	{
		$cookie = $this->getCreator();
		$c = $cookie->make('color', 'blue', 10);
		$value = $cookie->getEncrypter()->decrypt($c->getValue());
		$this->assertEquals('blue', $value);
		$this->assertFalse($c->isHttpOnly());
		$this->assertTrue($c->isSecure());
		$this->assertEquals('/domain', $c->getDomain());
		$this->assertEquals('/path', $c->getPath());

		$c2 = $cookie->forever('color', 'blue');
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

		$cookie = $this->getCreator();
		$value = $cookie->getEncrypter()->encrypt('bar');
		$value .= '111';
		$cookie->getRequest()->cookies->set('foo', $value);
		$this->assertNull($cookie->get('foo'));
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