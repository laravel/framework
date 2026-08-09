<?php

namespace Illuminate\Tests\Session;

use Illuminate\Contracts\Cache\Repository as CacheContract;
use Illuminate\Session\CacheBasedSessionHandler;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class CacheBasedSessionHandlerTest extends TestCase
{
    protected $cacheMock;

    protected $sessionHandler;

    protected function setUp(): void
    {
        $this->cacheMock = m::mock(CacheContract::class);
        $this->sessionHandler = new CacheBasedSessionHandler(cache: $this->cacheMock, minutes: 10);
    }

    public function test_open()
    {
        $result = $this->sessionHandler->open('path', 'session_name');
        $this->assertTrue($result);
    }

    public function test_close()
    {
        $result = $this->sessionHandler->close();
        $this->assertTrue($result);
    }

    public function test_read_returns_data_from_cache()
    {
        $this->cacheMock->expects('get')->with('session_id', '')->andReturn('session_data');

        $data = $this->sessionHandler->read(sessionId: 'session_id');
        $this->assertSame('session_data', $data);
    }

    public function test_read_returns_empty_string_if_no_data()
    {
        $this->cacheMock->expects('get')->with('some_id', '')->andReturn('');

        $data = $this->sessionHandler->read(sessionId: 'some_id');
        $this->assertSame('', $data);
    }

    public function test_write_stores_data_in_cache()
    {
        $this->cacheMock->expects('put')->with('session_id', 'session_data', 600) // 10 minutes in seconds
            ->andReturn(true);

        $result = $this->sessionHandler->write(sessionId: 'session_id', data: 'session_data');

        $this->assertTrue($result);
    }

    public function test_destroy_removes_data_from_cache()
    {
        $this->cacheMock->expects('forget')->with('session_id')->andReturn(true);

        $result = $this->sessionHandler->destroy(sessionId: 'session_id');

        $this->assertTrue($result);
    }

    public function test_gc_returns_zero()
    {
        $result = $this->sessionHandler->gc(lifetime: 120);

        $this->assertEquals(0, $result);
    }

    public function test_get_cache_returns_cache_instance()
    {
        $cacheInstance = $this->sessionHandler->getCache();

        $this->assertSame($this->cacheMock, $cacheInstance);
    }
}
