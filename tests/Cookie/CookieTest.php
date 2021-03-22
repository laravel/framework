<?php

namespace Illuminate\Tests\Cookie;

use PHPUnit\Framework\TestCase;
use Illuminate\Cookie\CookieJar;
use Illuminate\Support\Traits\Macroable;

class CookieTest extends TestCase
{
    public function getCookieJar(): CookieJar
    {
        return new CookieJar;
    }

    /**
     * @test
     */
    public function create_cookie()
    {
        $sut = $this->getCookieJar();

        $actualCookie = $sut->make('color', 'blue', 10, '/path', '/domain', true, false, false, 'lax');

        $this->assertSame('color', $actualCookie->getName());
        $this->assertSame('blue', $actualCookie->getValue());
        $this->assertFalse($actualCookie->isHttpOnly());
        $this->assertTrue($actualCookie->isSecure());
        $this->assertSame('/domain', $actualCookie->getDomain());
        $this->assertSame('/path', $actualCookie->getPath());
        $this->assertSame('lax', $actualCookie->getSameSite());
    }

    /**
     * @test
     */
    public function create_cookies_with_defaults()
    {
        $sut = $this->getCookieJar()
            ->setDefaultPathAndDomain('/path', '/domain', true, 'lax');

        $actualCookie = $sut->make('color', 'blue');

        $this->assertTrue($actualCookie->isSecure());
        $this->assertSame('/domain', $actualCookie->getDomain());
        $this->assertSame('/path', $actualCookie->getPath());
        $this->assertSame('lax', $actualCookie->getSameSite());
    }

    /**
     * @test
     */
    public function create_cookie_that_lasts_forever()
    {
        $sut = $this->getCookieJar()
            ->setDefaultPathAndDomain('/path', '/domain', true, 'strict');

        $actualCookie = $sut->forever('color', 'blue');

        $this->assertSame('color', $actualCookie->getName());
        $this->assertSame('blue', $actualCookie->getValue());
        $this->assertTrue($actualCookie->isSecure());
        $this->assertSame('/domain', $actualCookie->getDomain());
        $this->assertSame('/path', $actualCookie->getPath());
        $this->assertSame('strict', $actualCookie->getSameSite());
    }

    /**
     * @test
     */
    public function create_cookie_that_lasts_forever_with_defaults()
    {
        $sut = $this->getCookieJar();

        $actualCookie = $sut->forever('color', 'blue', '/path', '/domain', true, false, false, 'strict');

        $this->assertSame('color', $actualCookie->getName());
        $this->assertSame('blue', $actualCookie->getValue());
        $this->assertFalse($actualCookie->isHttpOnly());
        $this->assertTrue($actualCookie->isSecure());
        $this->assertSame('/domain', $actualCookie->getDomain());
        $this->assertSame('/path', $actualCookie->getPath());
        $this->assertSame('strict', $actualCookie->getSameSite());
    }

    /**
     * @test
     */
    public function forget_cookie()
    {
        $sut = $this->getCookieJar();

        $actualCookie = $sut->forget('color', '/path', '/domain');

        $this->assertNull($actualCookie->getValue());
        $this->assertEquals('/path', $actualCookie->getPath());
        $this->assertEquals('/domain', $actualCookie->getDomain());
        $this->assertTrue($actualCookie->getExpiresTime() < time());
    }

    /**
     * @test
     */
    public function forget_cookie_with_defaults()
    {
        $sut = $this->getCookieJar()
            ->setDefaultPathAndDomain('/path', '/domain');


        $actualCookie = $sut->forget('color');

        $this->assertNull($actualCookie->getValue());
        $this->assertEquals('/path', $actualCookie->getPath());
        $this->assertEquals('/domain', $actualCookie->getDomain());
        $this->assertTrue($actualCookie->getExpiresTime() < time());
    }

    /**
     * @test
     */
    public function queue_cookie_by_cookie_instance()
    {
        $sut = $this->getCookieJar();
        $cookie = $sut->make('foo', 'bar');

        $sut->queue($cookie);

        $this->assertTrue($sut->hasQueued('foo'));
        $this->assertCount(1, $sut->getQueuedCookies());
        $this->assertContains($cookie, $sut->getQueuedCookies());
        $this->assertSame($cookie, $sut->queued('foo'));
    }

    /**
     * @test
     */
    public function create_and_queue_cookie()
    {
        $sut = $this->getCookieJar();

        $sut->queue('color', 'blue', 10, '/path', '/domain', true, false, false, 'lax');

        $this->assertTrue($sut->hasQueued('color'));
        $this->assertCount(1, $sut->getQueuedCookies());
        $actualCookie = $sut->queued('color');
        $this->assertSame('color', $actualCookie->getName());
        $this->assertSame('blue', $actualCookie->getValue());
        $this->assertFalse($actualCookie->isHttpOnly());
        $this->assertTrue($actualCookie->isSecure());
        $this->assertSame('/domain', $actualCookie->getDomain());
        $this->assertSame('/path', $actualCookie->getPath());
        $this->assertSame('lax', $actualCookie->getSameSite());
    }

    /**
     * @test
     */
    public function create_and_queue_cookie_with_defaults()
    {
        $sut = $this->getCookieJar()
            ->setDefaultPathAndDomain('/path', '/domain', true, 'strict');

        $sut->queue('color', 'blue', 10);

        $this->assertTrue($sut->hasQueued('color'));
        $this->assertCount(1, $sut->getQueuedCookies());
        $actualCookie = $sut->queued('color');
        $this->assertSame('color', $actualCookie->getName());
        $this->assertSame('blue', $actualCookie->getValue());
        $this->assertTrue($actualCookie->isSecure());
        $this->assertSame('/domain', $actualCookie->getDomain());
        $this->assertSame('/path', $actualCookie->getPath());
        $this->assertSame('strict', $actualCookie->getSameSite());
    }

    /**
     * @test
     */
    public function get_queued_cookie_with_path()
    {
        $sut = $this->getCookieJar();
        $sut->queue($sut->make('foo', 'bar', 0, '/another-path'));
        $sut->queue($expectedCookie = $sut->make('foo', 'bar', 0, '/path'));

        $actualQueue = $sut->queued('foo', null, '/path');

        $this->assertEquals($expectedCookie, $actualQueue);
    }

    /**
     * @test
     */
    public function get_last_queued_cookie_without_path()
    {
        $sut = $this->getCookieJar();
        $sut->queue($sut->make('foo', 'laravel', 0, '/path'));
        $sut->queue($expectedCookie = $sut->make('foo', 'bar', 0, '/path'));

        $actualQueue = $sut->queued('foo');

        $this->assertEquals($expectedCookie, $actualQueue);
    }

    /**
     * @test
     */
    public function get_queued_cookies()
    {
        $sut = $this->getCookieJar();
        $sut->queue($expectedCookie = $sut->make('foo', 'bar'));

        $actualCookies = $sut->getQueuedCookies();

        $this->assertContains($expectedCookie, $actualCookies);
    }

    /**
     * @test
     */
    public function determine_if_cookie_is_queued()
    {
        $sut = $this->getCookieJar();
        $cookie = $sut->make('foo', 'bar');
        $sut->queue($cookie);

        $result = $sut->hasQueued('foo');

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function determine_if_cookie_is_not_queued_for_specific_path()
    {
        $sut = $this->getCookieJar();
        $cookie = $sut->make('foo', 'bar', 0, '/path');
        $sut->queue($cookie);

        $result = $sut->hasQueued('foo', '/another-path');

        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function determine_if_cookie_is_not_queued()
    {
        $sut = $this->getCookieJar();

        $result = $sut->hasQueued('foo');

        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function determine_if_cookie_is_queued_for_specific_path()
    {
        $sut = $this->getCookieJar();
        $sut->queue($cookie = $sut->make('foo', 'bar', 0, '/path'));

        $result = $sut->hasQueued('foo', '/path');

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function unqueue_cookie()
    {
        $cookie = $this->getCookieJar();
        $cookie->queue($cookie->make('foo', 'bar'));

        $cookie->unqueue('foo');

        $this->assertEmpty($cookie->getQueuedCookies());
    }

    /**
     * @test
     */
    public function unqueue_cookie_for_a_specific_path()
    {
        $cookie = $this->getCookieJar();
        $cookie->queue($cookie->make('foo', 'bar', 0, '/path'));

        $cookie->unqueue('foo', '/path');

        $this->assertEmpty($cookie->getQueuedCookies());
    }

    /**
     * @test
     */
    public function cookie_jar_is_macroable()
    {
        $sut = $this->getCookieJar();

        $isMacroable = in_array(Macroable::class, class_uses($sut), true);

        $this->assertTrue($isMacroable);
    }
}
