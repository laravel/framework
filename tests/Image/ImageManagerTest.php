<?php

namespace Illuminate\Tests\Image;

use Illuminate\Config\Repository;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Image\Driver;
use Illuminate\Contracts\Image\Transformation;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Image\Image;
use Illuminate\Image\ImageException;
use Illuminate\Image\ImageManager;
use Illuminate\Image\ImagePipeline;
use InvalidArgumentException;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class ImageManagerTest extends TestCase
{
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
        $app = $this->makeApp(['image.default' => 'custom']);

        $mockDriver = m::mock(Driver::class);

        $manager = new ImageManager($app);
        $manager->extend('custom', function ($app) use ($mockDriver) {
            return $mockDriver;
        });

        $this->assertSame($mockDriver, $manager->driver('custom'));
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

    public function test_from_storage_returns_image_from_storage_disk_path()
    {
        $contents = $this->fakeImageContents();

        $disk = m::mock();
        $disk->shouldReceive('get')
            ->once()
            ->with('images/avatar.jpg')
            ->andReturn($contents);

        $filesystem = m::mock(FilesystemFactory::class);
        $filesystem->shouldReceive('disk')
            ->once()
            ->with('public')
            ->andReturn($disk);

        $app = $this->makeApp([]);
        $app->shouldReceive('make')
            ->with(FilesystemFactory::class)
            ->andReturn($filesystem);

        $manager = new ImageManager($app);
        $image = $manager->fromStorage('images/avatar.jpg', 'public');

        $this->assertInstanceOf(Image::class, $image);
        $this->assertSame($contents, $image->toBytes());
    }

    public function test_from_storage_is_lazy()
    {
        $filesystem = m::mock(FilesystemFactory::class);
        $filesystem->shouldNotReceive('disk');

        $app = $this->makeApp([]);
        $app->shouldReceive('make')
            ->with(FilesystemFactory::class)
            ->andReturn($filesystem);

        $manager = new ImageManager($app);
        $image = $manager->fromStorage('images/avatar.jpg', 'public');

        $this->assertInstanceOf(Image::class, $image);
    }

    public function test_from_upload_returns_image_from_uploaded_file()
    {
        $file = UploadedFile::fake()->image('avatar.jpg', 100, 100);

        $app = $this->makeApp([]);
        $manager = new ImageManager($app);
        $image = $manager->fromUpload($file);

        $this->assertInstanceOf(Image::class, $image);
        $this->assertSame(file_get_contents($file->getRealPath()), $image->toBytes());
        $this->assertSame($file, $image->file());
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

    public function test_transform_using_applies_handlers_to_new_driver_instances()
    {
        $app = $this->makeApp([]);
        $driver = new class implements Driver
        {
            public array $handlers = [];

            public function process(string $contents, ImagePipeline $pipeline): string
            {
                return $contents;
            }

            public function transformUsing(string $transformation, callable $callback): static
            {
                $this->handlers[$transformation] = $callback;

                return $this;
            }
        };
        $transformation = new class implements Transformation {
            //
        };
        $callback = fn () => null;

        $manager = new ImageManager($app);
        $manager->extend('custom', fn () => $driver);
        $manager->transformUsing('custom', $transformation::class, $callback);

        $this->assertSame($callback, $manager->driver('custom')->handlers[$transformation::class]);
    }

    public function test_transform_using_applies_handlers_to_resolved_driver_instances()
    {
        $app = $this->makeApp([]);
        $driver = new class implements Driver
        {
            public array $handlers = [];

            public function process(string $contents, ImagePipeline $pipeline): string
            {
                return $contents;
            }

            public function transformUsing(string $transformation, callable $callback): static
            {
                $this->handlers[$transformation] = $callback;

                return $this;
            }
        };
        $transformation = new class implements Transformation {
            //
        };
        $callback = fn () => null;

        $manager = new ImageManager($app);
        $manager->extend('custom', fn () => $driver);
        $manager->driver('custom');
        $manager->transformUsing('custom', $transformation::class, $callback);

        $this->assertSame($callback, $driver->handlers[$transformation::class]);
    }

    protected function fakeImageContents(): string
    {
        $file = UploadedFile::fake()->image('test.jpg', 100, 100);

        return file_get_contents($file->getRealPath());
    }

    protected function makeApp(array $config): Application
    {
        $app = m::mock(Application::class, \ArrayAccess::class);

        $configRepo = new Repository($config);

        $app->shouldReceive('make')->with('config')->andReturn($configRepo)->byDefault();
        $app->shouldReceive('offsetGet')->with('config')->andReturn($configRepo);
        $app->shouldReceive('offsetExists')->andReturn(true);

        return $app;
    }
}
