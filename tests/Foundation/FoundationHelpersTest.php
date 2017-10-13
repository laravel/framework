<?php

namespace Illuminate\Tests\Foundation;

use Mockery as m;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Application;

class FoundationHelpersTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testCache()
    {
        $app = new Application;
        $app['cache'] = $cache = m::mock('stdClass');

        // 1. cache()
        $this->assertInstanceOf('stdClass', cache());

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
     * @expectedException \Exception
     * @expectedExceptionMessage You must specify an expiration time when setting a value in the cache.
     */
    public function testCacheThrowsAnExceptionIfAnExpirationIsNotProvided()
    {
        cache(['foo' => 'bar']);
    }

    public function testCarbon()
    {
        // carbon()
        $this->assertInstanceOf('Illuminate\Support\Carbon', carbon());

        // carbon('string', 'timezone')
        $this->assertEquals(13, carbon('2017-10-13 22:00:00')->day);
        $this->assertEquals('2017', carbon('first day of october 2017')->year);
        $this->assertFalse(carbon('today', 'America/Sao_Paulo')->utc);
        $this->assertFalse(carbon(null, 'America/Sao_Paulo')->utc);

        // carbon(\DateTime)
        $this->assertEquals(
            '22',
            carbon(\DateTime::createFromFormat('Y-m-d H', '2017-10-13 22'))->hour
        );
        $this->assertFalse(
            carbon(new \DateTime('now', new \DateTimeZone('America/Sao_Paulo')))->utc
        );
        $this->assertFalse(
            carbon(new \DateTime('now'), new \DateTimeZone('America/Sao_Paulo'))->utc
        );

        // Carbon helpers
        $this->assertEquals(Carbon::yesterday(), carbon()->yesterday());
        $this->assertEquals(Carbon::tomorrow(), carbon()->tomorrow());

        // Relative phrases
        $this->assertEquals(
            Carbon::now()->addMonth()->month, carbon('next month')->month
        );

        // Addition and Subtraction
        $this->assertEquals(
            Carbon::tomorrow()->addWeek(),
            carbon()->tomorrow()->addWeek()
        );
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

    public function testMixDoesNotIncludeHost()
    {
        $file = 'unversioned.css';

        app()->singleton('path.public', function () {
            return __DIR__;
        });

        touch(public_path('mix-manifest.json'));

        file_put_contents(public_path('mix-manifest.json'), json_encode([
            '/unversioned.css' => '/versioned.css',
        ]));

        $result = mix($file);

        $this->assertEquals('/versioned.css', $result);

        unlink(public_path('mix-manifest.json'));
    }
}
