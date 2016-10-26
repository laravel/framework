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

        $dispatcher->shouldReceive('fire')->once()->with('cache.missed', ['foo']);
        $this->assertFalse($repository->has('foo'));

        $dispatcher->shouldReceive('fire')->once()->with('cache.hit', ['baz', 'qux']);
        $this->assertTrue($repository->has('baz'));

        $dispatcher->shouldReceive('fire')->once()->with('cache.missed', ['foo', ['taylor']]);
        $dispatcher->shouldReceive('fire')->never();
        $this->assertFalse($repository->tags('taylor')->has('foo'));

        $dispatcher->shouldReceive('fire')->once()->with('cache.hit', ['baz', 'qux', ['taylor']]);
        $this->assertTrue($repository->tags('taylor')->has('baz'));
    }

    public function testGetTriggersEvents()
    {
        $dispatcher = $this->getDispatcher();
        $repository = $this->getRepository($dispatcher);

        $dispatcher->shouldReceive('fire')->once()->with('cache.missed', ['foo']);
        $this->assertNull($repository->get('foo'));

        $dispatcher->shouldReceive('fire')->once()->with('cache.hit', ['baz', 'qux']);
        $this->assertEquals('qux', $repository->get('baz'));

        $dispatcher->shouldReceive('fire')->once()->with('cache.missed', ['foo', ['taylor']]);
        $this->assertNull($repository->tags('taylor')->get('foo'));

        $dispatcher->shouldReceive('fire')->once()->with('cache.hit', ['baz', 'qux', ['taylor']]);
        $this->assertEquals('qux', $repository->tags('taylor')->get('baz'));
    }

    public function testPullTriggersEvents()
    {
        $dispatcher = $this->getDispatcher();
        $repository = $this->getRepository($dispatcher);

        $dispatcher->shouldReceive('fire')->once()->with('cache.hit', ['baz', 'qux']);
        $dispatcher->shouldReceive('fire')->once()->with('cache.delete', ['baz']);
        $this->assertEquals('qux', $repository->pull('baz'));

        $dispatcher->shouldReceive('fire')->once()->with('cache.hit', ['baz', 'qux', ['taylor']]);
        $dispatcher->shouldReceive('fire')->once()->with('cache.delete', ['baz', ['taylor']]);
        $this->assertEquals('qux', $repository->tags('taylor')->pull('baz'));
    }

    public function testPutTriggersEvents()
    {
        $dispatcher = $this->getDispatcher();
        $repository = $this->getRepository($dispatcher);

        $dispatcher->shouldReceive('fire')->once()->with('cache.write', ['foo', 'bar', 99]);
        $repository->put('foo', 'bar', 99);

        $dispatcher->shouldReceive('fire')->once()->with('cache.write', ['foo', 'bar', 99, ['taylor']]);
        $repository->tags('taylor')->put('foo', 'bar', 99);
    }

    public function testAddTriggersEvents()
    {
        $dispatcher = $this->getDispatcher();
        $repository = $this->getRepository($dispatcher);

        $dispatcher->shouldReceive('fire')->once()->with('cache.missed', ['foo']);
        $dispatcher->shouldReceive('fire')->once()->with('cache.write', ['foo', 'bar', 99]);
        $this->assertTrue($repository->add('foo', 'bar', 99));

        $dispatcher->shouldReceive('fire')->once()->with('cache.missed', ['foo', ['taylor']]);
        $dispatcher->shouldReceive('fire')->once()->with('cache.write', ['foo', 'bar', 99, ['taylor']]);
        $this->assertTrue($repository->tags('taylor')->add('foo', 'bar', 99));
    }

    public function testForeverTriggersEvents()
    {
        $dispatcher = $this->getDispatcher();
        $repository = $this->getRepository($dispatcher);

        $dispatcher->shouldReceive('fire')->once()->with('cache.write', ['foo', 'bar', 0]);
        $repository->forever('foo', 'bar');

        $dispatcher->shouldReceive('fire')->once()->with('cache.write', ['foo', 'bar', 0, ['taylor']]);
        $repository->tags('taylor')->forever('foo', 'bar');
    }

    public function testRememberTriggersEvents()
    {
        $dispatcher = $this->getDispatcher();
        $repository = $this->getRepository($dispatcher);

        $dispatcher->shouldReceive('fire')->once()->with('cache.missed', ['foo']);
        $dispatcher->shouldReceive('fire')->once()->with('cache.write', ['foo', 'bar', 99]);
        $this->assertEquals('bar', $repository->remember('foo', 99, function () {
            return 'bar';
        }));

        $dispatcher->shouldReceive('fire')->once()->with('cache.missed', ['foo', ['taylor']]);
        $dispatcher->shouldReceive('fire')->once()->with('cache.write', ['foo', 'bar', 99, ['taylor']]);
        $this->assertEquals('bar', $repository->tags('taylor')->remember('foo', 99, function () {
            return 'bar';
        }));
    }

    public function testRememberForeverTriggersEvents()
    {
        $dispatcher = $this->getDispatcher();
        $repository = $this->getRepository($dispatcher);

        $dispatcher->shouldReceive('fire')->once()->with('cache.missed', ['foo']);
        $dispatcher->shouldReceive('fire')->once()->with('cache.write', ['foo', 'bar', 0]);
        $this->assertEquals('bar', $repository->rememberForever('foo', function () {
            return 'bar';
        }));

        $dispatcher->shouldReceive('fire')->once()->with('cache.missed', ['foo', ['taylor']]);
        $dispatcher->shouldReceive('fire')->once()->with('cache.write', ['foo', 'bar', 0, ['taylor']]);
        $this->assertEquals('bar', $repository->tags('taylor')->rememberForever('foo', function () {
            return 'bar';
        }));
    }

    public function testForgetTriggersEvents()
    {
        $dispatcher = $this->getDispatcher();
        $repository = $this->getRepository($dispatcher);

        $dispatcher->shouldReceive('fire')->once()->with('cache.delete', ['baz']);
        $this->assertTrue($repository->forget('baz'));

        // $dispatcher->shouldReceive('fire')->once()->with('cache.delete', ['baz', ['taylor']]);
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
