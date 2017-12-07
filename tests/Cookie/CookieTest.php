<?php

namespace Illuminate\Tests\Cookie;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Cookie\CookieJar;
use Symfony\Component\HttpFoundation\Request;

class CookieTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testCookiesAreCreatedWithProperOptions()
    {
        $cookie = $this->getCreator();
        $cookie->setDefaultPathAndDomain('foo', 'bar');
        $c = $cookie->make('color', 'blue', 10, '/path', '/domain', true, false, false, 'lax');
        $this->assertEquals('blue', $c->getValue());
        $this->assertFalse($c->isHttpOnly());
        $this->assertTrue($c->isSecure());
        $this->assertEquals('/domain', $c->getDomain());
        $this->assertEquals('/path', $c->getPath());
        $this->assertEquals('lax', $c->getSameSite());

        $c2 = $cookie->forever('color', 'blue', '/path', '/domain', true, false, false, 'strict');
        $this->assertEquals('blue', $c2->getValue());
        $this->assertFalse($c2->isHttpOnly());
        $this->assertTrue($c2->isSecure());
        $this->assertEquals('/domain', $c2->getDomain());
        $this->assertEquals('/path', $c2->getPath());
        $this->assertEquals('strict', $c2->getSameSite());

        $c3 = $cookie->forget('color');
        $this->assertNull($c3->getValue());
        $this->assertTrue($c3->getExpiresTime() < time());
    }

    public function testCookiesAreCreatedWithProperOptionsUsingDefaultPathAndDomain()
    {
        $cookie = $this->getCreator();
        $cookie->setDefaultPathAndDomain('/path', '/domain', true, 'lax');
        $c = $cookie->make('color', 'blue');
        $this->assertEquals('blue', $c->getValue());
        $this->assertTrue($c->isSecure());
        $this->assertEquals('/domain', $c->getDomain());
        $this->assertEquals('/path', $c->getPath());
        $this->assertEquals('lax', $c->getSameSite());
    }

    public function testQueuedCookies()
    {
        $cookie = $this->getCreator();
        $this->assertEmpty($cookie->getQueuedCookies());
        $this->assertFalse($cookie->hasQueued('foo'));
        $cookie->queue($cookie->make('foo', 'bar'));
        $this->assertArrayHasKey('foo', $cookie->getQueuedCookies());
        $this->assertTrue($cookie->hasQueued('foo'));
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Cookie', $cookie->queued('foo'));
        $cookie->queue('qu', 'ux');
        $this->assertArrayHasKey('qu', $cookie->getQueuedCookies());
        $this->assertTrue($cookie->hasQueued('qu'));
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Cookie', $cookie->queued('qu'));
    }

    public function testUnqueue()
    {
        $cookie = $this->getCreator();
        $cookie->queue($cookie->make('foo', 'bar'));
        $this->assertArrayHasKey('foo', $cookie->getQueuedCookies());
        $cookie->unqueue('foo');
        $this->assertEmpty($cookie->getQueuedCookies());
    }

    public function getCreator()
    {
        return new CookieJar(Request::create('/foo', 'GET'), [
            'path'     => '/path',
            'domain'   => '/domain',
            'secure'   => true,
            'httpOnly' => false,
        ]);
    }
}
