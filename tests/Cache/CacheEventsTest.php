<?php

namespace Illuminate\Tests\Cache;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\Events\KeyForgotten;

class CacheEventsTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testHasTriggersEvents()
    {
        $dispatcher = $this->getDispatcher();
        $repository = $this->getRepository($dispatcher);

        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(CacheMissed::class, ['key' => 'foo']));
        $this->assertFalse($repository->has('foo'));

        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(CacheHit::class, ['key' => 'baz', 'value' => 'qux']));
        $this->assertTrue($repository->has('baz'));

        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(CacheMissed::class, ['key' => 'foo', 'tags' => ['taylor']]));
        $this->assertFalse($repository->tags('taylor')->has('foo'));

        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(CacheHit::class, ['key' => 'baz', 'value' => 'qux', 'tags' => ['taylor']]));
        $this->assertTrue($repository->tags('taylor')->has('baz'));
    }

    public function testGetTriggersEvents()
    {
        $dispatcher = $this->getDispatcher();
        $repository = $this->getRepository($dispatcher);

        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(CacheMissed::class, ['key' => 'foo']));
        $this->assertNull($repository->get('foo'));

        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(CacheHit::class, ['key' => 'baz', 'value' => 'qux']));
        $this->assertEquals('qux', $repository->get('baz'));

        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(CacheMissed::class, ['key' => 'foo', 'tags' => ['taylor']]));
        $this->assertNull($repository->tags('taylor')->get('foo'));

        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(CacheHit::class, ['key' => 'baz', 'value' => 'qux', 'tags' => ['taylor']]));
        $this->assertEquals('qux', $repository->tags('taylor')->get('baz'));
    }

    public function testPullTriggersEvents()
    {
        $dispatcher = $this->getDispatcher();
        $repository = $this->getRepository($dispatcher);

        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(CacheHit::class, ['key' => 'baz', 'value' => 'qux']));
        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(KeyForgotten::class, ['key' => 'baz']));
        $this->assertEquals('qux', $repository->pull('baz'));
    }

    public function testPullTriggersEventsUsingTags()
    {
        $dispatcher = $this->getDispatcher();
        $repository = $this->getRepository($dispatcher);

        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(CacheHit::class, ['key' => 'baz', 'value' => 'qux', 'tags' => ['taylor']]));
        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(KeyForgotten::class, ['key' => 'baz', 'tags' => ['taylor']]));
        $this->assertEquals('qux', $repository->tags('taylor')->pull('baz'));
    }

    public function testPutTriggersEvents()
    {
        $dispatcher = $this->getDispatcher();
        $repository = $this->getRepository($dispatcher);

        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(KeyWritten::class, ['key' => 'foo', 'value' => 'bar']));
        $repository->put('foo', 'bar', 99);

        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(KeyWritten::class, ['key' => 'foo', 'value' => 'bar', 'tags' => ['taylor']]));
        $repository->tags('taylor')->put('foo', 'bar', 99);
    }

    public function testAddTriggersEvents()
    {
        $dispatcher = $this->getDispatcher();
        $repository = $this->getRepository($dispatcher);

        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(CacheMissed::class, ['key' => 'foo']));
        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(KeyWritten::class, ['key' => 'foo', 'value' => 'bar']));
        $this->assertTrue($repository->add('foo', 'bar', 99));

        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(CacheMissed::class, ['key' => 'foo', 'tags' => ['taylor']]));
        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(KeyWritten::class, ['key' => 'foo', 'value' => 'bar', 'tags' => ['taylor']]));
        $this->assertTrue($repository->tags('taylor')->add('foo', 'bar', 99));
    }

    public function testForeverTriggersEvents()
    {
        $dispatcher = $this->getDispatcher();
        $repository = $this->getRepository($dispatcher);

        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(KeyWritten::class, ['key' => 'foo', 'value' => 'bar']));
        $repository->forever('foo', 'bar');

        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(KeyWritten::class, ['key' => 'foo', 'value' => 'bar', 'tags' => ['taylor']]));
        $repository->tags('taylor')->forever('foo', 'bar');
    }

    public function testRememberTriggersEvents()
    {
        $dispatcher = $this->getDispatcher();
        $repository = $this->getRepository($dispatcher);

        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(CacheMissed::class, ['key' => 'foo']));
        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(KeyWritten::class, ['key' => 'foo', 'value' => 'bar']));
        $this->assertEquals('bar', $repository->remember('foo', 99, function () {
            return 'bar';
        }));

        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(CacheMissed::class, ['key' => 'foo', 'tags' => ['taylor']]));
        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(KeyWritten::class, ['key' => 'foo', 'value' => 'bar', 'tags' => ['taylor']]));
        $this->assertEquals('bar', $repository->tags('taylor')->remember('foo', 99, function () {
            return 'bar';
        }));
    }

    public function testRememberForeverTriggersEvents()
    {
        $dispatcher = $this->getDispatcher();
        $repository = $this->getRepository($dispatcher);

        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(CacheMissed::class, ['key' => 'foo']));
        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(KeyWritten::class, ['key' => 'foo', 'value' => 'bar']));
        $this->assertEquals('bar', $repository->rememberForever('foo', function () {
            return 'bar';
        }));

        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(CacheMissed::class, ['key' => 'foo', 'tags' => ['taylor']]));
        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(KeyWritten::class, ['key' => 'foo', 'value' => 'bar', 'tags' => ['taylor']]));
        $this->assertEquals('bar', $repository->tags('taylor')->rememberForever('foo', function () {
            return 'bar';
        }));
    }

    public function testForgetTriggersEvents()
    {
        $dispatcher = $this->getDispatcher();
        $repository = $this->getRepository($dispatcher);

        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(KeyForgotten::class, ['key' => 'baz']));
        $this->assertTrue($repository->forget('baz'));

        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(KeyForgotten::class, ['key' => 'baz', 'tags' => ['taylor']]));
        $this->assertTrue($repository->tags('taylor')->forget('baz'));
    }

    protected function assertEventMatches($eventClass, $properties = [])
    {
        return m::on(function ($event) use ($eventClass, $properties) {
            if (! $event instanceof $eventClass) {
                return false;
            }

            foreach ($properties as $name => $value) {
                if ($event->$name != $value) {
                    return false;
                }
            }

            return true;
        });
    }

    protected function getDispatcher()
    {
        return m::mock('Illuminate\Events\Dispatcher');
    }

    protected function getRepository($dispatcher)
    {
        $repository = new \Illuminate\Cache\Repository(new \Illuminate\Cache\ArrayStore);
        $repository->put('baz', 'qux', 99);
        $repository->tags('taylor')->put('baz', 'qux', 99);
        $repository->setEventDispatcher($dispatcher);

        return $repository;
    }
}
