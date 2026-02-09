<?php

namespace Illuminate\Tests\Session;

use Illuminate\Session\NullSessionHandler;
use PHPUnit\Framework\TestCase;
use SessionHandlerInterface;

class NullSessionHandlerTest extends TestCase
{
    public function test_it_implements_the_session_handler_interface()
    {
        $this->assertInstanceOf(SessionHandlerInterface::class, new NullSessionHandler);
    }

    public function test_it_opens_the_session()
    {
        $handler = new NullSessionHandler;

        $this->assertTrue($handler->open('/tmp', 'PHPSESSID'));
    }

    public function test_it_closes_the_session()
    {
        $handler = new NullSessionHandler;

        $this->assertTrue($handler->close());
    }

    public function test_it_reads_empty_string_from_session()
    {
        $handler = new NullSessionHandler;

        $this->assertSame('', $handler->read('session-id'));
    }

    public function test_it_writes_to_session()
    {
        $handler = new NullSessionHandler;

        $this->assertTrue($handler->write('session-id', 'serialized-data'));
    }

    public function test_it_destroys_session()
    {
        $handler = new NullSessionHandler;

        $this->assertTrue($handler->destroy('session-id'));
    }

    public function test_it_performs_garbage_collection()
    {
        $handler = new NullSessionHandler;

        $this->assertSame(0, $handler->gc(3600));
    }

    public function test_read_always_returns_empty_string_regardless_of_previous_writes()
    {
        $handler = new NullSessionHandler;

        $handler->write('session-id', 'some-data');

        $this->assertSame('', $handler->read('session-id'));
    }
}
