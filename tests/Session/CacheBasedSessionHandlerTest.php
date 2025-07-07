<?php

namespace Illuminate\Tests\Session;

use Illuminate\Contracts\Cache\Repository as CacheContract;
use Illuminate\Session\CacheBasedSessionHandler;
use Illuminate\Support\Carbon;
use Mockery;
use PHPUnit\Framework\TestCase;

class CacheBasedSessionHandlerTest extends TestCase
{
    protected $cacheMock;

    protected $sessionHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cacheMock = Mockery::mock(CacheContract::class);
        $this->sessionHandler = new CacheBasedSessionHandler(cache: $this->cacheMock, minutes: 10);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
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
        $this->cacheMock->shouldReceive('get')->once()->with('session_id', '')->andReturn('session_data');

        $data = $this->sessionHandler->read(sessionId: 'session_id');
        $this->assertEquals('session_data', $data);
    }

    public function test_read_returns_empty_string_if_no_data()
    {
        $this->cacheMock->shouldReceive('get')->once()->with('some_id', '')->andReturn('');

        $data = $this->sessionHandler->read(sessionId: 'some_id');
        $this->assertEquals('', $data);
    }

    public function test_write_stores_data_in_cache()
    {
        $this->cacheMock->shouldReceive('put')->once()->with('session_id', 'session_data', 600) // 10 minutes in seconds
            ->andReturn(true);

        $result = $this->sessionHandler->write(sessionId: 'session_id', data: 'session_data');

        $this->assertTrue($result);
    }

    public function test_destroy_removes_data_from_cache()
    {
        $this->cacheMock->shouldReceive('forget')->once()->with('session_id')->andReturn(true);

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

    public function test_it_expires_correctly()
    {
        $this->cacheMock->shouldReceive('put')
            ->once()
            ->with('session_id', 'session_data', 120) // 2 minutes in seconds
            ->andReturn(true)
            ->shouldReceive('get')
            ->once()
            ->with('session_id', '')
            ->andReturn('session_data');

        $this->sessionHandler->setMinutes(2); // Set expiration time to 2 minutes
        $this->sessionHandler->write(sessionId: 'session_id', data: 'session_data');

        Carbon::setTestNow(Carbon::now()->addMinutes(1));

        $this->assertSame('session_data', $this->sessionHandler->read('session_id'));

        Carbon::setTestNow(Carbon::now()->addMinutes(3));

        $this->cacheMock->shouldReceive('get')
            ->once()
            ->with('session_id', '')
            ->andReturn('');
        $this->assertSame('', $this->sessionHandler->read('session_id'));
    }
}
