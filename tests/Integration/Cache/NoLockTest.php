<?php

namespace Illuminate\Tests\Integration\Cache;

use Illuminate\Support\Facades\Cache;
use Orchestra\Testbench\TestCase;

class NoLockTest extends TestCase
{
    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('cache.default', 'null');

        $app['config']->set('cache.stores', [
            'null' => [
                'driver' => 'null',
            ],
        ]);
    }

    public function testLocksCanAlwaysBeAcquiredAndReleased()
    {
        Cache::lock('foo')->forceRelease();

        $lock = Cache::lock('foo', 10);
        $this->assertTrue($lock->get());
        $this->assertTrue(Cache::lock('foo', 10)->get());
        $this->assertTrue($lock->release());
        $this->assertTrue($lock->release());
    }

    public function testLocksCanBlockForSeconds()
    {
        Cache::lock('foo')->forceRelease();
        $this->assertSame('taylor', Cache::lock('foo', 10)->block(1, function () {
            return 'taylor';
        }));

        Cache::lock('foo')->forceRelease();
        $this->assertTrue(Cache::lock('foo', 10)->block(1));
    }
}
