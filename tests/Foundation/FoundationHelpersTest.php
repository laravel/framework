<?php

namespace Illuminate\Tests\Foundation;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Application;
use Illuminate\Contracts\Bus\Dispatcher;

class FoundationHelpersTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testDispatch()
    {
        $app = new Application;
        $app[Dispatcher::class] = $dipatcher = m::mock('StdClass');

        // 1. dispatch(new Job)
        $job = m::mock('JobName');
        $dipatcher->shouldReceive('dispatch')->with($job);
        dispatch($job);

        // 1. dispatch(Job::class)
        $dipatcher->shouldReceive('dispatch')->once()->with(m::type('StdClass'));
        dispatch(\StdClass::class);

        // 1. dispatch(Job::class, 'arg1', 'arg2')
        $dipatcher->shouldReceive('dispatch')->once()->with(m::type('StdClass'));
        dispatch(\StdClass::class, 'arg1', 'arg2');
    }

    public function testCache()
    {
        $app = new Application;
        $app['cache'] = $cache = m::mock('StdClass');

        // 1. cache()
        $this->assertInstanceOf('StdClass', cache());

        // 2. cache(['foo' => 'bar'], 1);
        $cache->shouldReceive('put')->once()->with('foo', 'bar', 1);
        cache(['foo' => 'bar'], 1);

        // 3. cache('foo');
        $cache->shouldReceive('get')->once()->andReturn('bar');
        $this->assertEquals('bar', cache('foo'));

        // 4. cache('baz', 'default');
        $cache->shouldReceive('get')->once()->with('baz', 'default')->andReturn('default');
        $this->assertEquals('default', cache('baz', 'default'));
    }

    /**
     * @expectedException Exception
     */
    public function testCacheThrowsAnExceptionIfAnExpirationIsNotProvided()
    {
        cache(['foo' => 'bar']);
    }

    public function testUnversionedElixir()
    {
        $file = 'unversioned.css';

        app()->singleton('path.public', function () {
            return __DIR__;
        });

        touch(public_path($file));

        $this->assertEquals('/'.$file, elixir($file));

        unlink(public_path($file));
    }
}
