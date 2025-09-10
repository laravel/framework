<?php

namespace Illuminate\Tests\Cache;

use Illuminate\Cache\SessionStore;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use stdClass;

class CacheSessionStoreTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        Carbon::setTestNow(null);
    }

    public function testItemsCanBeSetAndRetrieved()
    {
        $store = new SessionStore(self::getSession());
        $result = $store->put('foo', 'bar', 10);
        $this->assertTrue($result);
        $this->assertSame('bar', $store->get('foo'));
    }

    public function testCacheTtl(): void
    {
        $store = new SessionStore(self::getSession());

        Carbon::setTestNow('2000-01-01 00:00:00.500'); // 500 milliseconds past
        $store->put('hello', 'world', 1);

        Carbon::setTestNow('2000-01-01 00:00:01.499'); // progress 0.999 seconds
        $this->assertSame('world', $store->get('hello'));

        Carbon::setTestNow('2000-01-01 00:00:01.500'); // progress 0.001 seconds. 1 second since putting into cache.
        $this->assertNull($store->get('hello'));
    }

    public function testMultipleItemsCanBeSetAndRetrieved()
    {
        $store = new SessionStore(self::getSession());
        $result = $store->put('foo', 'bar', 10);
        $resultMany = $store->putMany([
            'fizz' => 'buz',
            'quz' => 'baz',
        ], 10);
        $this->assertTrue($result);
        $this->assertTrue($resultMany);
        $this->assertEquals([
            'foo' => 'bar',
            'fizz' => 'buz',
            'quz' => 'baz',
            'norf' => null,
        ], $store->many(['foo', 'fizz', 'quz', 'norf']));
    }

    public function testItemsCanExpire()
    {
        Carbon::setTestNow(Carbon::now());

        $store = new SessionStore(self::getSession());

        $store->put('foo', 'bar', 10);
        Carbon::setTestNow(Carbon::now()->addSeconds(10)->addSecond());
        $result = $store->get('foo');

        $this->assertNull($result);
    }

    public function testStoreItemForeverProperlyStoresInArray()
    {
        $mock = $this->getMockBuilder(SessionStore::class)
            ->setConstructorArgs([self::getSession()])
            ->onlyMethods(['put'])
            ->getMock();
        $mock->expects($this->once())
            ->method('put')->with($this->equalTo('foo'), $this->equalTo('bar'), $this->equalTo(0))
            ->willReturn(true);
        $result = $mock->forever('foo', 'bar');
        $this->assertTrue($result);
    }

    public function testValuesCanBeIncremented()
    {
        $store = new SessionStore(self::getSession());
        $store->put('foo', 1, 10);
        $result = $store->increment('foo');
        $this->assertEquals(2, $result);
        $this->assertEquals(2, $store->get('foo'));

        $result = $store->increment('foo', 2);
        $this->assertEquals(4, $result);
        $this->assertEquals(4, $store->get('foo'));
    }

    public function testValuesGetCastedByIncrementOrDecrement()
    {
        $store = new SessionStore(self::getSession());
        $store->put('foo', '1', 10);
        $result = $store->increment('foo');
        $this->assertEquals(2, $result);
        $this->assertEquals(2, $store->get('foo'));

        $store->put('bar', '1', 10);
        $result = $store->decrement('bar');
        $this->assertEquals(0, $result);
        $this->assertEquals(0, $store->get('bar'));
    }

    public function testIncrementNonNumericValues()
    {
        $store = new SessionStore(self::getSession());
        $store->put('foo', 'I am string', 10);
        $result = $store->increment('foo');
        $this->assertEquals(1, $result);
        $this->assertEquals(1, $store->get('foo'));
    }

    public function testNonExistingKeysCanBeIncremented()
    {
        $store = new SessionStore(self::getSession());
        $result = $store->increment('foo');
        $this->assertEquals(1, $result);
        $this->assertEquals(1, $store->get('foo'));

        // Will be there forever
        Carbon::setTestNow(Carbon::now()->addYears(10));
        $this->assertEquals(1, $store->get('foo'));
    }

    public function testExpiredKeysAreIncrementedLikeNonExistingKeys()
    {
        Carbon::setTestNow(Carbon::now());

        $store = new SessionStore(self::getSession());

        $store->put('foo', 999, 10);
        Carbon::setTestNow(Carbon::now()->addSeconds(10)->addSecond());
        $result = $store->increment('foo');

        $this->assertEquals(1, $result);
    }

    public function testValuesCanBeDecremented()
    {
        $store = new SessionStore(self::getSession());
        $store->put('foo', 1, 10);
        $result = $store->decrement('foo');
        $this->assertEquals(0, $result);
        $this->assertEquals(0, $store->get('foo'));

        $result = $store->decrement('foo', 2);
        $this->assertEquals(-2, $result);
        $this->assertEquals(-2, $store->get('foo'));
    }

    public function testItemsCanBeRemoved()
    {
        $store = new SessionStore(self::getSession());
        $store->put('foo', 'bar', 10);
        $this->assertTrue($store->forget('foo'));
        $this->assertNull($store->get('foo'));
        $this->assertFalse($store->forget('foo'));
    }

    public function testItemsCanBeFlushed()
    {
        $store = new SessionStore(self::getSession());
        $store->put('foo', 'bar', 10);
        $store->put('baz', 'boom', 10);
        $result = $store->flush();
        $this->assertTrue($result);
        $this->assertNull($store->get('foo'));
        $this->assertNull($store->get('baz'));
    }

    public function testCacheKey()
    {
        $store = new SessionStore(self::getSession());
        $this->assertEmpty($store->getPrefix());
    }

    public function testItemKey()
    {
        $store = new SessionStore(self::getSession(), 'custom_prefix');
        $this->assertEquals('custom_prefix.foo', $store->itemKey('foo'));
    }

    public function testValuesAreStoredByReference()
    {
        $store = new SessionStore(self::getSession());
        $object = new stdClass;
        $object->foo = true;

        $store->put('object', $object, 10);
        $object->bar = true;

        $retrievedObject = $store->get('object');

        $this->assertTrue($retrievedObject->foo);
        $this->assertTrue($retrievedObject->bar);
    }

    public function testCanGetAll()
    {
        Carbon::setTestNow(Carbon::now());

        $store = new SessionStore(self::getSession());
        $store->put('foo', 'bar', 10);

        $this->assertEquals([
            'foo' => ['value' => 'bar', 'expiresAt' => Carbon::now()->addSeconds(10)->getPreciseTimestamp(3) / 1000],
        ], $store->all());
    }

    protected static function getSession()
    {
        return new Store(
            name: 'name',
            serialization: 'php',
            handler: new ArraySessionHandler(10),
            id: 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
        );
    }
}
