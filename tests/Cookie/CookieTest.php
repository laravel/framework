<?php

namespace Illuminate\Tests\Cookie;

use ArgumentCountError;
use Illuminate\Cookie\CookieJar;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionObject;
use Symfony\Component\HttpFoundation\Cookie;

class CookieTest extends TestCase
{
    public function testCookiesAreCreatedWithProperOptions()
    {
        $cookie = $this->getCreator();
        $cookie->setDefaultPathAndDomain('foo', 'bar');
        $c = $cookie->make('color', 'blue', 10, '/path', '/domain', true, false, false, 'lax');
        $this->assertSame('blue', $c->getValue());
        $this->assertFalse($c->isHttpOnly());
        $this->assertTrue($c->isSecure());
        $this->assertSame('/domain', $c->getDomain());
        $this->assertSame('/path', $c->getPath());
        $this->assertSame('lax', $c->getSameSite());

        $c2 = $cookie->forever('color', 'blue', '/path', '/domain', true, false, false, 'strict');
        $this->assertSame('blue', $c2->getValue());
        $this->assertFalse($c2->isHttpOnly());
        $this->assertTrue($c2->isSecure());
        $this->assertSame('/domain', $c2->getDomain());
        $this->assertSame('/path', $c2->getPath());
        $this->assertSame('strict', $c2->getSameSite());

        $c3 = $cookie->forget('color');
        $this->assertNull($c3->getValue());
        $this->assertTrue($c3->getExpiresTime() < time());
    }

    public function testCookiesAreCreatedWithProperOptionsUsingDefaultPathAndDomain(): void
    {
        $cookie = $this->getCreator();
        $cookie->setDefaultPathAndDomain('/path', '/domain', true, 'lax');
        $c = $cookie->make('color', 'blue');
        $this->assertSame('blue', $c->getValue());
        $this->assertTrue($c->isSecure());
        $this->assertSame('/domain', $c->getDomain());
        $this->assertSame('/path', $c->getPath());
        $this->assertSame('lax', $c->getSameSite());
        $this->assertTrue($c->isHttpOnly());
    }

    public function testCookiesCanSetSecureOptionUsingDefaultPathAndDomain()
    {
        $cookie = $this->getCreator();
        $cookie->setDefaultPathAndDomain('/path', '/domain', true, 'lax');
        $c = $cookie->make('color', 'blue', 10, null, null, false);
        $this->assertSame('blue', $c->getValue());
        $this->assertFalse($c->isSecure());
        $this->assertSame('/domain', $c->getDomain());
        $this->assertSame('/path', $c->getPath());
        $this->assertSame('lax', $c->getSameSite());
    }

    public function testQueuedCookiesWithoutName(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $cookie = $this->getCreator();
        $cookie->queue($cookie->make('', 'bar'));
    }

    public function testQueuedCookiesWithInvalidParameter(): void
    {
        $this->expectException(ArgumentCountError::class);

        $cookie = $this->getCreator();
        $cookie->queue('invalidCookie');
    }

    public function testQueuedCookiesWithHandlingEmptyValues(): void
    {
        $cookie = $this->getCreator();
        $cookie->queue($cookie->make('foo', ''));
        $this->assertTrue($cookie->hasQueued('foo'));
        $this->assertEquals('', $cookie->queued('foo')->getValue());
    }

    public function testQueuedCookiesWithRepeatedValue(): void
    {
        $cookie = $this->getCreator();
        $cookie->queue($cookie->make('foo', 'newBar'));
        $this->assertTrue($cookie->hasQueued('foo'));
        $this->assertEquals('newBar', $cookie->queued('foo')->getValue());

        $this->expectException(ArgumentCountError::class);
        $cookie->queue('invalidCookie');
    }

    public function testQueuedCookies(): void
    {
        $cookie = $this->getCreator();
        $this->assertEmpty($cookie->getQueuedCookies());
        $this->assertFalse($cookie->hasQueued('foo'));
        $cookie->queue($cookie->make('foo', 'bar'));
        $this->assertTrue($cookie->hasQueued('foo'));
        $this->assertInstanceOf(Cookie::class, $cookie->queued('foo'));
        $cookie->queue('qu', 'ux');
        $this->assertTrue($cookie->hasQueued('qu'));
        $this->assertInstanceOf(Cookie::class, $cookie->queued('qu'));
    }

    public function testQueuedWithPath(): void
    {
        $cookieJar = $this->getCreator();
        $cookieOne = $cookieJar->make('foo', 'bar', 0, '/path');
        $cookieTwo = $cookieJar->make('foo', 'rab', 0, '/');
        $cookieJar->queue($cookieOne);
        $cookieJar->queue($cookieTwo);
        $this->assertEquals($cookieOne, $cookieJar->queued('foo', null, '/path'));
        $this->assertEquals($cookieTwo, $cookieJar->queued('foo', null, '/'));
    }

    public function testQueuedWithoutPath(): void
    {
        $cookieJar = $this->getCreator();
        $cookieOne = $cookieJar->make('foo', 'bar', 0, '/path');
        $cookieTwo = $cookieJar->make('foo', 'rab', 0, '/');
        $cookieJar->queue($cookieOne);
        $cookieJar->queue($cookieTwo);
        $this->assertEquals($cookieTwo, $cookieJar->queued('foo'));
    }

    public function testHasQueued(): void
    {
        $cookieJar = $this->getCreator();
        // test empty queue
        $this->assertFalse($cookieJar->hasQueued('foo'));

        $cookie = $cookieJar->make('foo', 'bar');
        $cookieJar->queue($cookie);
        $this->assertTrue($cookieJar->hasQueued('foo'));
        $this->assertFalse($cookieJar->hasQueued('nonexistent'));
    }

    public function testHasQueuedWithPath(): void
    {
        $cookieJar = $this->getCreator();
        $cookieOne = $cookieJar->make('foo', 'bar', 0, '/path');
        $cookieTwo = $cookieJar->make('foo', 'rab', 0, '/');
        $cookieJar->queue($cookieOne);
        $cookieJar->queue($cookieTwo);
        $this->assertTrue($cookieJar->hasQueued('foo', '/path'));
        $this->assertTrue($cookieJar->hasQueued('foo', '/'));
        $this->assertFalse($cookieJar->hasQueued('foo', '/wrongPath'));
    }

    public function testExpire()
    {
        $cookieJar = $this->getCreator();
        $this->assertCount(0, $cookieJar->getQueuedCookies());

        $cookieJar->expire('foobar', '/path', '/domain');

        $cookie = $cookieJar->queued('foobar');
        $this->assertSame('foobar', $cookie->getName());
        $this->assertEquals(null, $cookie->getValue());
        $this->assertSame('/path', $cookie->getPath());
        $this->assertSame('/domain', $cookie->getDomain());
        $this->assertTrue($cookie->getExpiresTime() < time());
        $this->assertCount(1, $cookieJar->getQueuedCookies());
    }

    public function testUnqueue(): void
    {
        $cookie = $this->getCreator();

        $cookie->unqueue('nonexistent');
        $this->assertEmpty($cookie->getQueuedCookies());

        $cookie->queue($cookie->make('foo', 'bar'));
        $cookie->unqueue('foo');
        $this->assertEmpty($cookie->getQueuedCookies());
    }

    public function testUnqueueMultipleCookies(): void
    {
        $cookie = $this->getCreator();
        $cookie->queue($cookie->make('foo', 'bar'));
        $cookie->queue($cookie->make('baz', 'qux'));
        $cookie->unqueue('foo');
        $this->assertTrue($cookie->hasQueued('baz'));
        $this->assertFalse($cookie->hasQueued('foo'));
    }

    public function testUnqueueWithPath(): void
    {
        $cookieJar = $this->getCreator();
        $cookieOne = $cookieJar->make('foo', 'bar', 0, '/path');
        $cookieTwo = $cookieJar->make('foo', 'rab', 0, '/');
        $cookieJar->queue($cookieOne);
        $cookieJar->queue($cookieTwo);
        $cookieJar->unqueue('foo', '/path');
        $this->assertEquals(['foo' => ['/' => $cookieTwo]], $this->getQueuedPropertyValue($cookieJar));
    }

    public function testUnqueueOnlyCookieForName(): void
    {
        $cookieJar = $this->getCreator();
        $cookie = $cookieJar->make('foo', 'bar', 0, '/path');
        $cookieJar->queue($cookie);
        $cookieJar->unqueue('foo', '/path');
        $this->assertEmpty($this->getQueuedPropertyValue($cookieJar));
    }

    public function testCookieJarIsMacroable()
    {
        $cookie = $this->getCreator();
        $cookie->macro('foo', function () {
            return 'bar';
        });
        $this->assertSame('bar', $cookie->foo());
    }

    public function testQueueCookie(): void
    {
        $cookieJar = $this->getCreator();
        $cookie = $cookieJar->make('foo', 'bar', 0, '/path');
        $cookieJar->queue($cookie);
        $this->assertEquals(['foo' => ['/path' => $cookie]], $this->getQueuedPropertyValue($cookieJar));
    }

    public function testQueueWithCreatingNewCookie(): void
    {
        $cookieJar = $this->getCreator();
        $cookieJar->queue('foo', 'bar', 0, '/path');
        $this->assertEquals(
            ['foo' => ['/path' => new Cookie('foo', 'bar', 0, '/path')]],
            $this->getQueuedPropertyValue($cookieJar)
        );
    }

    public function testGetQueuedCookies(): void
    {
        $cookieJar = $this->getCreator();
        $cookieOne = $cookieJar->make('foo', 'bar', 0, '/path');
        $cookieTwo = $cookieJar->make('foo', 'rab', 0, '/');
        $cookieThree = $cookieJar->make('oof', 'bar', 0, '/path');
        $cookieJar->queue($cookieOne);
        $cookieJar->queue($cookieTwo);
        $cookieJar->queue($cookieThree);
        $this->assertEquals(
            [$cookieOne, $cookieTwo, $cookieThree],
            $cookieJar->getQueuedCookies()
        );
    }

    public function testFlushQueuedCookies(): void
    {
        $cookieJar = $this->getCreator();
        $cookieJar->queue($cookieJar->make('foo', 'bar', 0, '/path'));
        $cookieJar->queue($cookieJar->make('foo', 'rab', 0, '/'));
        $this->assertCount(2, $cookieJar->getQueuedCookies());

        $cookieJar->flushQueuedCookies();
        $this->assertEmpty($cookieJar->getQueuedCookies());
    }

    public function getCreator()
    {
        return new CookieJar;
    }

    private function getQueuedPropertyValue(CookieJar $cookieJar)
    {
        $property = (new ReflectionObject($cookieJar))->getProperty('queued');

        return $property->getValue($cookieJar);
    }
}
