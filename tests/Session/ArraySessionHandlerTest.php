<?php

namespace Illuminate\Tests\Session;

use Illuminate\Session\ArraySessionHandler;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use SessionHandlerInterface;

class ArraySessionHandlerTest extends TestCase
{
    public function testItImplementsTheSessionHandlerInterface()
    {
        $this->assertInstanceOf(SessionHandlerInterface::class, new ArraySessionHandler(10));
    }

    public function testItInitializesTheSession()
    {
        $handler = new ArraySessionHandler(10);

        $this->assertTrue($handler->open('', ''));
    }

    public function testItClosesTheSession()
    {
        $handler = new ArraySessionHandler(10);

        $this->assertTrue($handler->close());
    }

    public function testItReadsDataFromTheSession()
    {
        $handler = new ArraySessionHandler(10);

        $handler->write('foo', 'bar');

        $this->assertSame('bar', $handler->read('foo'));
    }

    public function testItReadsDataFromAnAlmostExpiredSession()
    {
        $handler = new ArraySessionHandler(10);

        $handler->write('foo', 'bar');

        Carbon::setTestNow(Carbon::now()->addMinutes(10));
        $this->assertSame('bar', $handler->read('foo'));
        Carbon::setTestNow();
    }

    public function testItReadsDataFromAnExpiredSession()
    {
        $handler = new ArraySessionHandler(10);

        $handler->write('foo', 'bar');

        Carbon::setTestNow(Carbon::now()->addMinutes(10)->addSecond());
        $this->assertSame('', $handler->read('foo'));
        Carbon::setTestNow();
    }

    public function testItReadsDataFromANonExistingSession()
    {
        $handler = new ArraySessionHandler(10);

        $this->assertSame('', $handler->read('foo'));
    }

    public function testItWritesSessionData()
    {
        $handler = new ArraySessionHandler(10);

        $this->assertTrue($handler->write('foo', 'bar'));
        $this->assertSame('bar', $handler->read('foo'));

        $this->assertTrue($handler->write('foo', 'baz'));
        $this->assertSame('baz', $handler->read('foo'));
    }

    public function testItDestroysASession()
    {
        $handler = new ArraySessionHandler(10);

        $this->assertTrue($handler->destroy('foo'));

        $handler->write('foo', 'bar');

        $this->assertTrue($handler->destroy('foo'));
        $this->assertSame('', $handler->read('foo'));
    }

    public function testItCleansUpOldSessions()
    {
        $handler = new ArraySessionHandler(10);

        $this->assertTrue($handler->gc(300));

        $handler->write('foo', 'bar');
        $this->assertTrue($handler->gc(300));
        $this->assertSame('bar', $handler->read('foo'));

        Carbon::setTestNow(Carbon::now()->addSecond());

        $handler->write('baz', 'qux');

        Carbon::setTestNow(Carbon::now()->addMinutes(5));

        $this->assertTrue($handler->gc(300));
        $this->assertSame('', $handler->read('foo'));
        $this->assertSame('qux', $handler->read('baz'));

        Carbon::setTestNow();
    }
}
