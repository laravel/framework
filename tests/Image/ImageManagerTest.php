<?php

namespace Illuminate\Tests\Image;

use Illuminate\Config\Repository;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\Filesystem\Filesystem as FilesystemContract;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Image\Driver;
use Illuminate\Contracts\Image\Transformation;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application as FoundationApplication;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\UploadedFile;
use Illuminate\Image\Image;
use Illuminate\Image\ImageException;
use Illuminate\Image\ImageManager;
use Illuminate\Image\ImagePipeline;
use InvalidArgumentException;
use JMac\Testing\Double;
use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;
use PHPUnit\Framework\TestCase;

class ImageManagerTest extends TestCase
{
    use VerifiesDoubles;

    public function test_default_driver_returns_configured_value()
    {
        $app = $this->makeApp(['images.default' => 'imagick']);

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
        $app = $this->makeApp(['images.default' => 'custom']);

        $mockDriver = new ImageManagerTestDriverStub;

        $manager = new ImageManager($app);
        $manager->extend('custom', function ($app) use ($mockDriver) {
            return $mockDriver;
        });

        $this->assertSame($mockDriver, $manager->driver('custom'));
    }

    public function test_driver_caches_resolved_instances()
    {
        $app = $this->makeApp([]);

        $mockDriver = new ImageManagerTestDriverStub;

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

        $this->expectExceptionObject(new InvalidArgumentException('Image driver [nonexistent] is not supported.'));

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

        $app = $this->makeApp([]);
        $app->instance(Filesystem::class, new Filesystem);

        $manager = new ImageManager($app);
        $image = $manager->fromPath($path);

        $this->assertInstanceOf(Image::class, $image);
        $this->assertNotEmpty($image->toBytes());
    }

    public function test_from_path_is_lazy()
    {
        $filesystem = Double::for(Filesystem::class);
        $filesystem->expects('get')->never();

        $app = $this->makeApp([]);

        $manager = new ImageManager($app);
        $image = $manager->fromPath('/some/path.jpg');

        $this->assertInstanceOf(Image::class, $image);
    }

    public function test_from_storage_returns_image_from_storage_disk_path()
    {
        $contents = $this->fakeImageContents();

        $disk = Double::for(FilesystemContract::class);
        $disk->expects('get')->with('images/avatar.jpg')->returns($contents);

        $filesystem = Double::for(FilesystemFactory::class);
        $filesystem->expects('disk')->with('public')->returns($disk);

        $app = $this->makeApp([]);
        $app->instance(FilesystemFactory::class, $filesystem);

        $manager = new ImageManager($app);
        $image = $manager->fromStorage('images/avatar.jpg', 'public');

        $this->assertInstanceOf(Image::class, $image);
        $this->assertSame($contents, $image->toBytes());
    }

    public function test_from_storage_accepts_backed_enum_disk()
    {
        $contents = $this->fakeImageContents();

        $disk = Double::for(FilesystemContract::class);
        $disk->expects('get')->with('images/avatar.jpg')->returns($contents);

        $filesystem = Double::for(FilesystemFactory::class);
        $filesystem->expects('disk')->with('public')->returns($disk);

        $app = $this->makeApp([]);
        $app->instance(FilesystemFactory::class, $filesystem);

        $manager = new ImageManager($app);
        $image = $manager->fromStorage('images/avatar.jpg', ImageDiskStub::Public);

        $this->assertInstanceOf(Image::class, $image);
        $this->assertSame($contents, $image->toBytes());
    }

    public function test_from_storage_is_lazy()
    {
        $filesystem = Double::for(FilesystemFactory::class);
        $filesystem->expects('disk')->never();

        $app = $this->makeApp([]);

        $manager = new ImageManager($app);
        $image = $manager->fromStorage('images/avatar.jpg', 'public');

        $this->assertInstanceOf(Image::class, $image);
    }

    public function test_from_stream_returns_image()
    {
        $contents = $this->fakeImageContents();
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $contents);
        rewind($stream);

        $app = $this->makeApp([]);
        $manager = new ImageManager($app);
        $image = $manager->fromStream($stream);

        $this->assertInstanceOf(Image::class, $image);
        $this->assertSame($contents, $image->toBytes());

        fclose($stream);
    }

    public function test_from_stream_is_lazy()
    {
        $contents = $this->fakeImageContents();
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $contents);
        rewind($stream);

        $app = $this->makeApp([]);
        $manager = new ImageManager($app);
        $image = $manager->fromStream($stream);

        $this->assertInstanceOf(Image::class, $image);
        $this->assertSame(0, ftell($stream));

        fclose($stream);
    }

    public function test_from_stream_contents_are_only_read_once()
    {
        $contents = $this->fakeImageContents();
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $contents);
        rewind($stream);

        $app = $this->makeApp([]);
        $manager = new ImageManager($app);
        $image = $manager->fromStream($stream);

        $this->assertSame($contents, $image->toBytes());
        $this->assertSame($contents, $image->toBytes());
        $this->assertSame([100, 100], $image->dimensions());

        fclose($stream);
    }

    public function test_from_stream_contents_are_shared_between_clones()
    {
        $contents = $this->fakeImageContents();
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $contents);
        rewind($stream);

        $app = $this->makeApp([]);
        $manager = new ImageManager($app);
        $image = $manager->fromStream($stream);

        $first = $image->usingGd();
        $second = $image->usingImagick();

        $this->assertSame($contents, $first->toBytes());
        $this->assertSame($contents, $second->toBytes());

        fclose($stream);
    }

    public function test_lazy_contents_are_not_shared_between_different_images()
    {
        $app = $this->makeApp([]);
        $manager = new ImageManager($app);

        $first = $manager->fromBase64(base64_encode('first'));
        $second = $manager->fromBase64(base64_encode('second'));

        $this->assertSame('first', $first->toBytes());
        $this->assertSame('second', $second->toBytes());
        $this->assertSame('first', $first->toBytes());
    }

    public function test_from_url_is_only_fetched_once()
    {
        $contents = $this->fakeImageContents();

        $http = new HttpFactory;
        $http->fake([
            'https://example.com/photo.jpg' => HttpFactory::response($contents, 200, ['Content-Type' => 'image/jpeg']),
        ]);

        $app = $this->makeApp([]);
        $app->instance(HttpFactory::class, $http);

        $manager = new ImageManager($app);
        $image = $manager->fromUrl('https://example.com/photo.jpg');

        $this->assertSame($contents, $image->toBytes());
        $this->assertSame($contents, $image->toBytes());
        $this->assertSame([100, 100], $image->dimensions());

        $http->assertSentCount(1);
    }

    public function test_from_stream_throws_for_invalid_data()
    {
        $stream = fopen('php://memory', 'r+');

        $app = $this->makeApp([]);
        $manager = new ImageManager($app);

        $this->expectExceptionObject(new ImageException('Invalid stream image data.'));

        $manager->fromStream($stream)->toBytes();

        fclose($stream);
    }

    public function test_from_upload_returns_image_from_uploaded_file()
    {
        $file = UploadedFile::fake()->image('avatar.jpg', 100, 100);

        $app = $this->makeApp([]);
        $manager = new ImageManager($app);
        $image = $manager->fromUpload($file);

        $this->assertInstanceOf(Image::class, $image);
        $this->assertStringEqualsFile($file->getRealPath(), $image->toBytes());
        $this->assertSame($file, $image->file());
    }

    public function test_from_url_returns_image()
    {
        $contents = $this->fakeImageContents();

        $http = new HttpFactory;
        $http->fake([
            'https://example.com/photo.jpg' => HttpFactory::response(
                $contents,
                200,
                ['Content-Type' => 'image/jpeg'],
            ),
        ]);

        $app = $this->makeApp([]);
        $app->instance(HttpFactory::class, $http);

        $manager = new ImageManager($app);
        $image = $manager->fromUrl('https://example.com/photo.jpg');

        $this->assertInstanceOf(Image::class, $image);
        $this->assertSame($contents, $image->toBytes());
    }

    public function test_from_url_throws_for_unsuccessful_response()
    {
        $http = new HttpFactory;
        $http->fake([
            'https://example.com/missing.jpg' => HttpFactory::response('not found', 404),
        ]);

        $app = $this->makeApp([]);
        $app->instance(HttpFactory::class, $http);

        $manager = new ImageManager($app);
        $image = $manager->fromUrl('https://example.com/missing.jpg');

        $this->assertInstanceOf(Image::class, $image);

        $this->expectException(RequestException::class);

        $image->toBytes();
    }

    public function test_from_url_is_lazy()
    {
        $http = new HttpFactory;
        $http->fake();

        $app = $this->makeApp([]);
        $app->instance(HttpFactory::class, $http);

        $manager = new ImageManager($app);
        $image = $manager->fromUrl('https://example.com/photo.jpg');

        $this->assertInstanceOf(Image::class, $image);

        $http->assertNothingSent();
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

        $this->expectExceptionObject(new ImageException('Invalid base64 image data.'));

        $manager->fromBase64('!!!not-base64!!!')->toBytes();
    }

    public function test_extend_overwrites_previous_registration()
    {
        $app = $this->makeApp([]);

        $firstDriver = new ImageManagerTestDriverStub;
        $secondDriver = new ImageManagerTestDriverStub;

        $manager = new ImageManager($app);
        $manager->extend('custom', fn () => $firstDriver);
        $manager->extend('custom', fn () => $secondDriver);

        $this->assertSame($secondDriver, $manager->driver('custom'));
    }

    public function test_driver_caches_separately_by_name()
    {
        $app = $this->makeApp([]);

        $driver1 = new ImageManagerTestDriverStub;
        $driver2 = new ImageManagerTestDriverStub;

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

            public function dominantColor(string $contents): string
            {
                return '#000000';
            }

            public function dimensions(string $contents): array
            {
                return [0, 0];
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

            public function dominantColor(string $contents): string
            {
                return '#000000';
            }

            public function dimensions(string $contents): array
            {
                return [0, 0];
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
        $app = new FoundationApplication;

        $app->instance('config', new Repository($config));

        return $app;
    }
}

enum ImageDiskStub: string
{
    case Public = 'public';
}

class ImageManagerTestDriverStub implements Driver
{
    public function process(string $contents, ImagePipeline $pipeline): string
    {
        return $contents;
    }

    public function dominantColor(string $contents): string
    {
        return '#000000';
    }

    public function dimensions(string $contents): array
    {
        return [0, 0];
    }

    public function transformUsing(string $transformation, callable $callback): static
    {
        return $this;
    }
}
