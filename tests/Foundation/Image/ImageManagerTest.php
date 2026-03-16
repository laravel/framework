<?php

namespace Illuminate\Tests\Foundation\Image;

use Illuminate\Contracts\Image\Driver;
use Illuminate\Foundation\Image\ImageManager;
use InvalidArgumentException;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class ImageManagerTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function test_default_driver_returns_configured_value()
    {
        $app = $this->makeApp(['image.default' => 'imagick']);

        $manager = new ImageManager($app);

        $this->assertSame('imagick', $manager->getDefaultDriver());
    }

    public function test_default_driver_falls_back_to_gd()
    {
        $app = $this->makeApp([]);

        $manager = new ImageManager($app);

        $this->assertSame('gd', $manager->getDefaultDriver());
    }

    public function test_extend_registers_custom_driver()
    {
        $app = $this->makeApp(['image.default' => 'cloudflare']);

        $mockDriver = m::mock(Driver::class);

        $manager = new ImageManager($app);
        $manager->extend('cloudflare', function ($app) use ($mockDriver) {
            return $mockDriver;
        });

        $this->assertSame($mockDriver, $manager->driver('cloudflare'));
    }

    public function test_driver_caches_resolved_instances()
    {
        $app = $this->makeApp([]);

        $mockDriver = m::mock(Driver::class);

        $manager = new ImageManager($app);
        $manager->extend('custom', function () use ($mockDriver) {
            return $mockDriver;
        });

        $first = $manager->driver('custom');
        $second = $manager->driver('custom');

        $this->assertSame($first, $second);
    }

    public function test_throws_for_unsupported_driver()
    {
        $app = $this->makeApp([]);

        $manager = new ImageManager($app);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Image driver [nonexistent] is not supported.');

        $manager->driver('nonexistent');
    }

    protected function makeApp(array $config): \Illuminate\Contracts\Foundation\Application
    {
        $app = m::mock(\Illuminate\Contracts\Foundation\Application::class, \ArrayAccess::class);

        $configRepo = new \ArrayObject($config);

        $app->shouldReceive('offsetGet')->with('config')->andReturn($configRepo);
        $app->shouldReceive('offsetExists')->andReturn(true);

        return $app;
    }
}
