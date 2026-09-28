<?php

namespace Illuminate\Tests\Session;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Session\CacheBasedSessionHandler;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class CacheBasedSessionHandlerTest extends TestCase
{
    protected $cache;

    protected $sessionHandler;

    protected function setUp(): void
    {
        $this->cache = new Repository(new ArrayStore);
        $this->sessionHandler = new CacheBasedSessionHandler(cache: $this->cache, minutes: 10);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
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

    public function test_it_creates_session_ids()
    {
        $sessionId = $this->sessionHandler->create_sid();

        $this->assertIsString($sessionId);
        $this->assertNotEmpty($sessionId);
    }

    public function test_validate_id_checks_cache()
    {
        $this->assertFalse($this->sessionHandler->validateId('session_id'));

        $this->sessionHandler->write(sessionId: 'session_id', data: 'session_data');

        $this->assertTrue($this->sessionHandler->validateId('session_id'));
    }

    public function test_read_returns_data_from_cache()
    {
        $this->sessionHandler->write(sessionId: 'session_id', data: 'session_data');

        $data = $this->sessionHandler->read(sessionId: 'session_id');
        $this->assertSame('session_data', $data);
    }

    public function test_read_returns_empty_string_if_no_data()
    {
        $data = $this->sessionHandler->read(sessionId: 'some_id');
        $this->assertSame('', $data);
    }

    public function test_write_stores_data_in_cache()
    {
        Carbon::setTestNow('2000-01-01 00:00:00');

        $result = $this->sessionHandler->write(sessionId: 'session_id', data: 'session_data');
        $this->assertTrue($result);

        // 10 minutes (600 seconds) later, still within the TTL.
        Carbon::setTestNow('2000-01-01 00:09:59');
        $this->assertSame('session_data', $this->sessionHandler->read(sessionId: 'session_id'));

        // just past the 10 minute TTL, the entry should have expired.
        Carbon::setTestNow('2000-01-01 00:10:01');
        $this->assertSame('', $this->sessionHandler->read(sessionId: 'session_id'));
    }

    public function test_destroy_removes_data_from_cache()
    {
        $this->sessionHandler->write(sessionId: 'session_id', data: 'session_data');

        $result = $this->sessionHandler->destroy(sessionId: 'session_id');

        $this->assertTrue($result);
        $this->assertSame('', $this->sessionHandler->read(sessionId: 'session_id'));
    }

    public function test_gc_returns_zero()
    {
        $result = $this->sessionHandler->gc(lifetime: 120);

        $this->assertEquals(0, $result);
    }

    public function test_get_cache_returns_cache_instance()
    {
        $cacheInstance = $this->sessionHandler->getCache();

        $this->assertSame($this->cache, $cacheInstance);
    }
}
