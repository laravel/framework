<?php

namespace Illuminate\Tests\Cache;

use DateTime;
use DateInterval;
use Mockery as m;
use DateTimeImmutable;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class CacheRepositoryTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testGetReturnsValueFromCache()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('get')->once()->with('foo')->andReturn('bar');
        $this->assertEquals('bar', $repo->get('foo'));
    }

    public function testGetReturnsMultipleValuesFromCacheWhenGivenAnArray()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('many')->once()->with(['foo', 'bar'])->andReturn(['foo' => 'bar', 'bar' => 'baz']);
        $this->assertEquals(['foo' => 'bar', 'bar' => 'baz'], $repo->get(['foo', 'bar']));
    }

    public function testGetReturnsMultipleValuesFromCacheWhenGivenAnArrayWithDefaultValues()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('many')->once()->with(['foo', 'bar'])->andReturn(['foo' => null, 'bar' => 'baz']);
        $this->assertEquals(['foo' => 'default', 'bar' => 'baz'], $repo->get(['foo' => 'default', 'bar']));
    }

    public function testDefaultValueIsReturned()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('get')->andReturn(null);
        $this->assertEquals('bar', $repo->get('foo', 'bar'));
        $this->assertEquals('baz', $repo->get('boom', function () {
            return 'baz';
        }));
    }

    public function testSettingDefaultCacheTime()
    {
        $repo = $this->getRepository();
        $repo->setDefaultCacheTime(10);
        $this->assertEquals(10, $repo->getDefaultCacheTime());
    }

    public function testHasMethod()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('get')->once()->with('foo')->andReturn(null);
        $repo->getStore()->shouldReceive('get')->once()->with('bar')->andReturn('bar');

        $this->assertTrue($repo->has('bar'));
        $this->assertFalse($repo->has('foo'));
    }

    public function testRememberMethodCallsPutAndReturnsDefault()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('get')->andReturn(null);
        $repo->getStore()->shouldReceive('put')->once()->with('foo', 'bar', 10);
        $result = $repo->remember('foo', 10, function () {
            return 'bar';
        });
        $this->assertEquals('bar', $result);

        /*
         * Use Carbon object...
         */
        Carbon::setTestNow(Carbon::now());

        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('get')->andReturn(null);
        $repo->getStore()->shouldReceive('put')->once()->with('foo', 'bar', 602 / 60);
        $repo->getStore()->shouldReceive('put')->once()->with('baz', 'qux', 598 / 60);
        $result = $repo->remember('foo', Carbon::now()->addMinutes(10)->addSeconds(2), function () {
            return 'bar';
        });
        $this->assertEquals('bar', $result);
        $result = $repo->remember('baz', Carbon::now()->addMinutes(10)->subSeconds(2), function () {
            return 'qux';
        });
        $this->assertEquals('qux', $result);

        Carbon::setTestNow();
    }

    public function testRememberForeverMethodCallsForeverAndReturnsDefault()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('get')->andReturn(null);
        $repo->getStore()->shouldReceive('forever')->once()->with('foo', 'bar');
        $result = $repo->rememberForever('foo', function () {
            return 'bar';
        });
        $this->assertEquals('bar', $result);
    }

    public function testPuttingMultipleItemsInCache()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('putMany')->once()->with(['foo' => 'bar', 'bar' => 'baz'], 1);
        $repo->put(['foo' => 'bar', 'bar' => 'baz'], 1);
    }

    public function testSettingMultipleItemsInCache()
    {
        // Alias of PuttingMultiple
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('putMany')->once()->with(['foo' => 'bar', 'bar' => 'baz'], 1);
        $repo->setMultiple(['foo' => 'bar', 'bar' => 'baz'], 1);
    }

    public function testPutWithDatetimeInPastOrZeroSecondsDoesntSaveItem()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('put')->never();
        $repo->put('foo', 'bar', Carbon::now()->subMinutes(10));
        $repo->put('foo', 'bar', Carbon::now());
    }

    public function testAddWithDatetimeInPastOrZeroSecondsReturnsImmediately()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('add', 'get', 'put')->never();
        $result = $repo->add('foo', 'bar', Carbon::now()->subMinutes(10));
        $this->assertFalse($result);
        $result = $repo->add('foo', 'bar', Carbon::now());
        $this->assertFalse($result);
    }

    public function testCacheAddCallsRedisStoreAdd()
    {
        $store = m::mock(\Illuminate\Cache\RedisStore::class);
        $store->shouldReceive('add')->once()->with('k', 'v', 60)->andReturn(true);
        $repository = new \Illuminate\Cache\Repository($store);
        $this->assertTrue($repository->add('k', 'v', 60));
    }

    public function dataProviderTestGetMinutes()
    {
        return [
            [Carbon::now()->addMinutes(5)],
            [(new DateTime('2030-07-25 12:13:14 UTC'))->modify('+5 minutes')],
            [(new DateTimeImmutable('2030-07-25 12:13:14 UTC'))->modify('+5 minutes')],
            [new DateInterval('PT5M')],
            [5],
        ];
    }

    /**
     * @dataProvider dataProviderTestGetMinutes
     * @param mixed $duration
     */
    public function testGetMinutes($duration)
    {
        Carbon::setTestNow(Carbon::parse('2030-07-25 12:13:14 UTC'));

        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('put')->with($key = 'foo', $value = 'bar', 5);
        $repo->put($key, $value, $duration);

        Carbon::setTestNow();
    }

    public function testRegisterMacroWithNonStaticCall()
    {
        $repo = $this->getRepository();
        $repo::macro(__CLASS__, function () {
            return 'Taylor';
        });
        $this->assertEquals($repo->{__CLASS__}(), 'Taylor');
    }

    public function testForgettingCacheKey()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('forget')->once()->with('a-key')->andReturn(true);
        $repo->forget('a-key');
    }

    public function testRemovingCacheKey()
    {
        // Alias of Forget
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('forget')->once()->with('a-key')->andReturn(true);
        $repo->delete('a-key');
    }

    public function testSettingCache()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('put')->with($key = 'foo', $value = 'bar', 1);
        $repo->set($key, $value, 1);
    }

    public function testClearingWholeCache()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('flush')->andReturn(true);
        $repo->clear();
    }

    public function testGettingMultipleValuesFromCache()
    {
        $keys = ['key1', 'key2', 'key3'];
        $default = ['key2' => 5];

        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('many')->once()->with(['key2', 'key1', 'key3'])->andReturn(['key1' => 1, 'key2' => null, 'key3' => null]);
        $this->assertEquals(['key1' => 1, 'key2' => 5, 'key3' => null], $repo->getMultiple($keys, $default));
    }

    public function testRemovingMultipleKeys()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('forget')->once()->with('a-key')->andReturn(true);
        $repo->getStore()->shouldReceive('forget')->once()->with('a-second-key')->andReturn(true);
        $repo->deleteMultiple(['a-key', 'a-second-key']);
    }

    protected function getRepository()
    {
        $dispatcher = new \Illuminate\Events\Dispatcher(m::mock('Illuminate\Container\Container'));
        $repository = new \Illuminate\Cache\Repository(m::mock('Illuminate\Contracts\Cache\Store'));

        $repository->setEventDispatcher($dispatcher);

        return $repository;
    }
}
