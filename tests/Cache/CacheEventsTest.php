<?php

use Mockery as m;

class CacheEventTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testHasTriggersEvents()
    {
        $dispatcher = $this->getDispatcher();
        $repository = $this->getRepository($dispatcher);

        $dispatcher->shouldReceive('fire')->once()->with(m::type('Illuminate\Cache\Events\CacheMissed'));
        $this->assertFalse($repository->has('foo'));

        $dispatcher->shouldReceive('fire')->once()->with(m::type('Illuminate\Cache\Events\CacheHit'));
        $this->assertTrue($repository->has('baz'));

        $dispatcher->shouldReceive('fire')->once()->with(m::type('Illuminate\Cache\Events\CacheMissed'));
        $dispatcher->shouldReceive('fire')->never();
        $this->assertFalse($repository->tags('taylor')->has('foo'));

        $dispatcher->shouldReceive('fire')->once()->with(m::type('Illuminate\Cache\Events\CacheHit'));
        $this->assertTrue($repository->tags('taylor')->has('baz'));
    }

    public function testGetTriggersEvents()
    {
        $dispatcher = $this->getDispatcher();
        $repository = $this->getRepository($dispatcher);

        $dispatcher->shouldReceive('fire')->once()->with(m::type('Illuminate\Cache\Events\CacheMissed'));
        $this->assertNull($repository->get('foo'));

        $dispatcher->shouldReceive('fire')->once()->with(m::type('Illuminate\Cache\Events\CacheHit'));
        $this->assertEquals('qux', $repository->get('baz'));

        $dispatcher->shouldReceive('fire')->once()->with(m::type('Illuminate\Cache\Events\CacheMissed'));
        $this->assertNull($repository->tags('taylor')->get('foo'));

        $dispatcher->shouldReceive('fire')->once()->with(m::type('Illuminate\Cache\Events\CacheHit'));
        $this->assertEquals('qux', $repository->tags('taylor')->get('baz'));
    }

    public function testPullTriggersEvents()
    {
        $dispatcher = $this->getDispatcher();
        $repository = $this->getRepository($dispatcher);

        $dispatcher->shouldReceive('fire')->once()->with(m::type('Illuminate\Cache\Events\CacheHit'));
        $dispatcher->shouldReceive('fire')->once()->with(m::type('Illuminate\Cache\Events\KeyForgotten'));
        $this->assertEquals('qux', $repository->pull('baz'));
    }

    public function testPullTriggersEventsUsingTags()
    {
        $dispatcher = $this->getDispatcher();
        $repository = $this->getRepository($dispatcher);

        $dispatcher->shouldReceive('fire')->once()->with(m::type('Illuminate\Cache\Events\CacheHit'));
        $dispatcher->shouldReceive('fire')->once()->with(m::type('Illuminate\Cache\Events\KeyForgotten'));
        $this->assertEquals('qux', $repository->tags('taylor')->pull('baz'));
    }

    public function testPutTriggersEvents()
    {
        $dispatcher = $this->getDispatcher();
        $repository = $this->getRepository($dispatcher);

        $dispatcher->shouldReceive('fire')->once()->with(m::type('Illuminate\Cache\Events\KeyWritten'));
        $repository->put('foo', 'bar', 99);

        $dispatcher->shouldReceive('fire')->once()->with(m::type('Illuminate\Cache\Events\KeyWritten'));
        $repository->tags('taylor')->put('foo', 'bar', 99);
    }

    public function testAddTriggersEvents()
    {
        $dispatcher = $this->getDispatcher();
        $repository = $this->getRepository($dispatcher);

        $dispatcher->shouldReceive('fire')->once()->with(m::type('Illuminate\Cache\Events\CacheMissed'));
        $dispatcher->shouldReceive('fire')->once()->with(m::type('Illuminate\Cache\Events\KeyWritten'));
        $this->assertTrue($repository->add('foo', 'bar', 99));

        $dispatcher->shouldReceive('fire')->once()->with(m::type('Illuminate\Cache\Events\CacheMissed'));
        $dispatcher->shouldReceive('fire')->once()->with(m::type('Illuminate\Cache\Events\KeyWritten'));
        $this->assertTrue($repository->tags('taylor')->add('foo', 'bar', 99));
    }

    public function testForeverTriggersEvents()
    {
        $dispatcher = $this->getDispatcher();
        $repository = $this->getRepository($dispatcher);

        $dispatcher->shouldReceive('fire')->once()->with(m::type('Illuminate\Cache\Events\KeyWritten'));
        $repository->forever('foo', 'bar');

        $dispatcher->shouldReceive('fire')->once()->with(m::type('Illuminate\Cache\Events\KeyWritten'));
        $repository->tags('taylor')->forever('foo', 'bar');
    }

    public function testRememberTriggersEvents()
    {
        $dispatcher = $this->getDispatcher();
        $repository = $this->getRepository($dispatcher);

        $dispatcher->shouldReceive('fire')->once()->with(m::type('Illuminate\Cache\Events\CacheMissed'));
        $dispatcher->shouldReceive('fire')->once()->with(m::type('Illuminate\Cache\Events\KeyWritten'));
        $this->assertEquals('bar', $repository->remember('foo', 99, function () {
            return 'bar';
        }));

        $dispatcher->shouldReceive('fire')->once()->with(m::type('Illuminate\Cache\Events\CacheMissed'));
        $dispatcher->shouldReceive('fire')->once()->with(m::type('Illuminate\Cache\Events\KeyWritten'));
        $this->assertEquals('bar', $repository->tags('taylor')->remember('foo', 99, function () {
            return 'bar';
        }));
    }

    public function testRememberForeverTriggersEvents()
    {
        $dispatcher = $this->getDispatcher();
        $repository = $this->getRepository($dispatcher);

        $dispatcher->shouldReceive('fire')->once()->with(m::type('Illuminate\Cache\Events\CacheMissed'));
        $dispatcher->shouldReceive('fire')->once()->with(m::type('Illuminate\Cache\Events\KeyWritten'));
        $this->assertEquals('bar', $repository->rememberForever('foo', function () {
            return 'bar';
        }));

        $dispatcher->shouldReceive('fire')->once()->with(m::type('Illuminate\Cache\Events\CacheMissed'));
        $dispatcher->shouldReceive('fire')->once()->with(m::type('Illuminate\Cache\Events\KeyWritten'));
        $this->assertEquals('bar', $repository->tags('taylor')->rememberForever('foo', function () {
            return 'bar';
        }));
    }

    public function testForgetTriggersEvents()
    {
        $dispatcher = $this->getDispatcher();
        $repository = $this->getRepository($dispatcher);

        $dispatcher->shouldReceive('fire')->once()->with(m::type('Illuminate\Cache\Events\KeyForgotten'));
        $this->assertTrue($repository->forget('baz'));

        // $dispatcher->shouldReceive('fire')->once()->with(m::type('Illuminate\Cache\Events\CacheMissed'));
        // $this->assertTrue($repository->tags('taylor')->forget('baz'));
    }

    protected function getDispatcher()
    {
        return m::mock('Illuminate\Events\Dispatcher');
    }

    protected function getRepository($dispatcher)
    {
        $repository = new \Illuminate\Cache\Repository(new \Illuminate\Cache\ArrayStore());
        $repository->put('baz', 'qux', 99);
        $repository->tags('taylor')->put('baz', 'qux', 99);
        $repository->setEventDispatcher($dispatcher);

        return $repository;
    }
}
