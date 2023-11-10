<?php

namespace Illuminate\Tests\Session;

use Illuminate\Session\ArraySessionHandler;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use SessionHandlerInterface;

class ArraySessionHandlerTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        Carbon::setTestNow(null);
    }

    public function test_it_implements_the_session_handler_interface()
    {
        $this->assertInstanceOf(SessionHandlerInterface::class, new ArraySessionHandler(10));
    }

    public function test_it_initializes_the_session()
    {
        $handler = new ArraySessionHandler(10);

        $this->assertTrue($handler->open('', ''));
    }

    public function test_it_closes_the_session()
    {
        $handler = new ArraySessionHandler(10);

        $this->assertTrue($handler->close());
    }

    public function test_it_reads_data_from_the_session()
    {
        $handler = new ArraySessionHandler(10);

        $handler->write('foo', 'bar');

        $this->assertSame('bar', $handler->read('foo'));
    }

    public function test_it_reads_data_from_an_almost_expired_session()
    {
        $handler = new ArraySessionHandler(10);

        $handler->write('foo', 'bar');

        Carbon::setTestNow(Carbon::now()->addMinutes(10));
        $this->assertSame('bar', $handler->read('foo'));
    }

    public function test_it_reads_data_from_an_expired_session()
    {
        $handler = new ArraySessionHandler(10);

        $handler->write('foo', 'bar');

        Carbon::setTestNow(Carbon::now()->addMinutes(10)->addSecond());
        $this->assertSame('', $handler->read('foo'));
    }

    public function test_it_reads_data_from_a_non_existing_session()
    {
        $handler = new ArraySessionHandler(10);

        $this->assertSame('', $handler->read('foo'));
    }

    public function test_it_writes_session_data()
    {
        $handler = new ArraySessionHandler(10);

        $this->assertTrue($handler->write('foo', 'bar'));
        $this->assertSame('bar', $handler->read('foo'));

        $this->assertTrue($handler->write('foo', 'baz'));
        $this->assertSame('baz', $handler->read('foo'));
    }

    public function test_it_destroys_a_session()
    {
        $handler = new ArraySessionHandler(10);

        $this->assertTrue($handler->destroy('foo'));

        $handler->write('foo', 'bar');

        $this->assertTrue($handler->destroy('foo'));
        $this->assertSame('', $handler->read('foo'));
    }

    public function test_it_cleans_up_old_sessions()
    {
        $handler = new ArraySessionHandler(10);

        $this->assertSame(0, $handler->gc(300));

        $handler->write('foo', 'bar');
        $this->assertSame(0, $handler->gc(300));
        $this->assertSame('bar', $handler->read('foo'));

        Carbon::setTestNow(Carbon::now()->addSecond());

        $handler->write('baz', 'qux');

        Carbon::setTestNow(Carbon::now()->addMinutes(5));

        $this->assertSame(1, $handler->gc(300));
        $this->assertSame('', $handler->read('foo'));
        $this->assertSame('qux', $handler->read('baz'));
    }
}
