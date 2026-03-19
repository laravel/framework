<?php

namespace Illuminate\Tests\Foundation\Image;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Image\Driver;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Image\Image;
use Illuminate\Foundation\Image\ImageManager;
use Illuminate\Http\UploadedFile;
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

    public function test_read_returns_image_with_contents()
    {
        $app = $this->makeApp([]);
        $manager = new ImageManager($app);

        $contents = $this->fakeImageContents();
        $image = $manager->read($contents);

        $this->assertInstanceOf(Image::class, $image);
        $this->assertSame($contents, $image->toBytes());
    }

    public function test_from_returns_image_from_file_path()
    {
        $file = UploadedFile::fake()->image('test.jpg', 100, 100);
        $path = $file->getRealPath();

        $filesystem = m::mock(Filesystem::class);
        $filesystem->shouldReceive('get')
            ->once()
            ->with($path)
            ->andReturn(file_get_contents($path));

        $app = $this->makeApp([]);
        $app->shouldReceive('make')
            ->with(Filesystem::class)
            ->andReturn($filesystem);

        $manager = new ImageManager($app);
        $image = $manager->from($path);

        $this->assertInstanceOf(Image::class, $image);
        $this->assertNotEmpty($image->toBytes());
    }

    public function test_from_is_lazy()
    {
        $filesystem = m::mock(Filesystem::class);
        $filesystem->shouldNotReceive('get');

        $app = $this->makeApp([]);
        $app->shouldReceive('make')
            ->with(Filesystem::class)
            ->andReturn($filesystem);

        $manager = new ImageManager($app);
        $image = $manager->from('/some/path.jpg');

        $this->assertInstanceOf(Image::class, $image);
    }

    protected function fakeImageContents(): string
    {
        $file = UploadedFile::fake()->image('test.jpg', 100, 100);

        return file_get_contents($file->getRealPath());
    }

    protected function makeApp(array $config): Application
    {
        $app = m::mock(Application::class, \ArrayAccess::class);

        $configRepo = new \ArrayObject($config);

        $app->shouldReceive('offsetGet')->with('config')->andReturn($configRepo);
        $app->shouldReceive('offsetExists')->andReturn(true);

        return $app;
    }
}
