<?php

use Mockery as m;
use Carbon\Carbon;

class CacheRepositoryTest extends PHPUnit_Framework_TestCase
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

    public function testDefaultValueIsReturned()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('get')->andReturn(null);
        $this->assertEquals('bar', $repo->get('foo', 'bar'));
        $this->assertEquals('baz', $repo->get('boom', function () { return 'baz'; }));
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
        $result = $repo->remember('foo', 10, function () { return 'bar'; });
        $this->assertEquals('bar', $result);

        /*
         * Use Carbon object...
         */
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('get')->andReturn(null);
        $repo->getStore()->shouldReceive('put')->once()->with('foo', 'bar', 10);
        $repo->getStore()->shouldReceive('put')->once()->with('baz', 'qux', 9);
        $result = $repo->remember('foo', Carbon::now()->addMinutes(10)->addSeconds(2), function () { return 'bar'; });
        $this->assertEquals('bar', $result);
        $result = $repo->remember('baz', Carbon::now()->addMinutes(10)->subSeconds(2), function () { return 'qux'; });
        $this->assertEquals('qux', $result);
    }

    public function testRememberForeverMethodCallsForeverAndReturnsDefault()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('get')->andReturn(null);
        $repo->getStore()->shouldReceive('forever')->once()->with('foo', 'bar');
        $result = $repo->rememberForever('foo', function () { return 'bar'; });
        $this->assertEquals('bar', $result);
    }

    public function testPutWithDatetimeInPastOrZeroMinutesDoesntSaveItem()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('put')->never();
        $repo->put('foo', 'bar', Carbon::now()->subMinutes(10));
        $repo->put('foo', 'bar', Carbon::now()->addSeconds(5));
    }

    public function testAddWithDatetimeInPastOrZeroMinutesReturnsImmediately()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('add', 'get', 'put')->never();
        $result = $repo->add('foo', 'bar', Carbon::now()->subMinutes(10));
        $this->assertFalse($result);
        $result = $repo->add('foo', 'bar', Carbon::now()->addSeconds(5));
        $this->assertFalse($result);
    }

    public function testRegisterMacroWithNonStaticCall()
    {
        $repo = $this->getRepository();
        $repo::macro(__CLASS__, function () { return 'Taylor'; });
        $this->assertEquals($repo->{__CLASS__}(), 'Taylor');
    }

    protected function getRepository()
    {
        $dispatcher = new \Illuminate\Events\Dispatcher(m::mock('Illuminate\Container\Container'));
        $repository = new Illuminate\Cache\Repository(m::mock('Illuminate\Contracts\Cache\Store'));

        $repository->setEventDispatcher($dispatcher);

        return $repository;
    }
}
