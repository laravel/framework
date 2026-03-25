<?php

namespace Illuminate\Tests\Foundation\Image;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Image\Driver;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Image\Image;
use Illuminate\Foundation\Image\ImageException;
use Illuminate\Foundation\Image\ImageManager;
use Illuminate\Http\Client\Factory as HttpFactory;
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

    public function test_from_bytes_returns_image_with_contents()
    {
        $app = $this->makeApp([]);
        $manager = new ImageManager($app);

        $contents = $this->fakeImageContents();
        $image = $manager->fromBytes($contents);

        $this->assertInstanceOf(Image::class, $image);
        $this->assertSame($contents, $image->toBytes());
    }

    public function test_from_path_returns_image_from_file_path()
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
        $image = $manager->fromPath($path);

        $this->assertInstanceOf(Image::class, $image);
        $this->assertNotEmpty($image->toBytes());
    }

    public function test_from_path_is_lazy()
    {
        $filesystem = m::mock(Filesystem::class);
        $filesystem->shouldNotReceive('get');

        $app = $this->makeApp([]);
        $app->shouldReceive('make')
            ->with(Filesystem::class)
            ->andReturn($filesystem);

        $manager = new ImageManager($app);
        $image = $manager->fromPath('/some/path.jpg');

        $this->assertInstanceOf(Image::class, $image);
    }

    public function test_from_url_returns_image()
    {
        $contents = $this->fakeImageContents();

        $http = m::mock(HttpFactory::class);
        $response = m::mock();
        $response->shouldReceive('body')->andReturn($contents);
        $http->shouldReceive('get')->with('https://example.com/photo.jpg')->andReturn($response);

        $app = $this->makeApp([]);
        $app->shouldReceive('make')
            ->with(HttpFactory::class)
            ->andReturn($http);

        $manager = new ImageManager($app);
        $image = $manager->fromUrl('https://example.com/photo.jpg');

        $this->assertInstanceOf(Image::class, $image);
        $this->assertSame($contents, $image->toBytes());
    }

    public function test_from_url_is_lazy()
    {
        $http = m::mock(HttpFactory::class);
        $http->shouldNotReceive('get');

        $app = $this->makeApp([]);
        $app->shouldReceive('make')
            ->with(HttpFactory::class)
            ->andReturn($http);

        $manager = new ImageManager($app);
        $image = $manager->fromUrl('https://example.com/photo.jpg');

        $this->assertInstanceOf(Image::class, $image);
    }

    public function test_from_base64_returns_image()
    {
        $contents = $this->fakeImageContents();
        $base64 = base64_encode($contents);

        $app = $this->makeApp([]);
        $manager = new ImageManager($app);

        $image = $manager->fromBase64($base64);

        $this->assertInstanceOf(Image::class, $image);
        $this->assertSame($contents, $image->toBytes());
    }

    public function test_from_base64_throws_for_invalid_data()
    {
        $app = $this->makeApp([]);
        $manager = new ImageManager($app);

        $this->expectException(ImageException::class);
        $this->expectExceptionMessage('Invalid base64 image data.');

        $manager->fromBase64('!!!not-base64!!!')->toBytes();
    }

    public function test_extend_overwrites_previous_registration()
    {
        $app = $this->makeApp([]);

        $firstDriver = m::mock(Driver::class);
        $secondDriver = m::mock(Driver::class);

        $manager = new ImageManager($app);
        $manager->extend('custom', fn () => $firstDriver);
        $manager->extend('custom', fn () => $secondDriver);

        $this->assertSame($secondDriver, $manager->driver('custom'));
    }

    public function test_driver_caches_separately_by_name()
    {
        $app = $this->makeApp([]);

        $driver1 = m::mock(Driver::class);
        $driver2 = m::mock(Driver::class);

        $manager = new ImageManager($app);
        $manager->extend('one', fn () => $driver1);
        $manager->extend('two', fn () => $driver2);

        $this->assertSame($driver1, $manager->driver('one'));
        $this->assertSame($driver2, $manager->driver('two'));
        $this->assertNotSame($manager->driver('one'), $manager->driver('two'));
    }

    public function test_prune_orphaned_uses_default_driver()
    {
        $app = $this->makeApp([]);
        $pruned = false;

        $driver = new class($pruned) implements Driver
        {
            public function __construct(private &$pruned)
            {
            }

            public function process(string $contents, \Illuminate\Foundation\Image\PendingImageOptions $options): string
            {
                return $contents;
            }

            public function pruneOrphaned(): void
            {
                $this->pruned = true;
            }
        };

        $manager = new ImageManager($app);
        $manager->extend('gd', fn () => $driver);

        $manager->pruneOrphaned();

        $this->assertTrue($pruned);
    }

    public function test_prune_orphaned_accepts_specific_driver()
    {
        $app = $this->makeApp([]);
        $pruned = false;

        $cloudflareDriver = new class($pruned) implements Driver
        {
            public function __construct(private &$pruned)
            {
            }

            public function process(string $contents, \Illuminate\Foundation\Image\PendingImageOptions $options): string
            {
                return $contents;
            }

            public function pruneOrphaned(): void
            {
                $this->pruned = true;
            }
        };

        $manager = new ImageManager($app);
        $manager->extend('gd', fn () => m::mock(Driver::class));
        $manager->extend('cloudflare', fn () => $cloudflareDriver);

        $manager->pruneOrphaned('cloudflare');

        $this->assertTrue($pruned);
    }

    public function test_prune_orphaned_is_noop_when_driver_does_not_support_it()
    {
        $app = $this->makeApp([]);

        $driver = m::mock(Driver::class);

        $manager = new ImageManager($app);
        $manager->extend('gd', fn () => $driver);

        $manager->pruneOrphaned();

        $this->assertTrue(true);
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
