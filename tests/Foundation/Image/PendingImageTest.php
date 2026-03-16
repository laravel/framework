<?php

namespace Illuminate\Tests\Foundation\Image;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Image\PendingImage;
use Illuminate\Http\UploadedFile;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class PendingImageTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function test_cover_sets_options()
    {
        $image = $this->makePendingImage();
        $result = $image->cover(100, 200);

        $this->assertSame($image, $result);

        $options = (new \ReflectionProperty($image, 'options'))->getValue($image);

        $this->assertSame(100, $options->coverWidth);
        $this->assertSame(200, $options->coverHeight);
    }

    public function test_optimize_sets_options()
    {
        $image = $this->makePendingImage();
        $image->optimize('webp');

        $options = (new \ReflectionProperty($image, 'options'))->getValue($image);

        $this->assertSame('webp', $options->format);
    }

    public function test_optimize_has_defaults()
    {
        $image = $this->makePendingImage();
        $image->optimize();

        $options = (new \ReflectionProperty($image, 'options'))->getValue($image);

        $this->assertSame('webp', $options->format);
    }

    public function test_options_can_be_chained()
    {
        $image = $this->makePendingImage();
        $image->cover(100, 100)->optimize(format: 'webp');

        $options = (new \ReflectionProperty($image, 'options'))->getValue($image);

        $this->assertSame(100, $options->coverWidth);
        $this->assertSame(100, $options->coverHeight);
        $this->assertSame('webp', $options->format);
    }

    public function test_using_sets_driver_override()
    {
        $image = $this->makePendingImage();
        $result = $image->using('cloudflare');

        $this->assertSame($image, $result);

        $driver = (new \ReflectionProperty($image, 'driver'))->getValue($image);

        $this->assertSame('cloudflare', $driver);
    }

    public function test_file_returns_underlying_uploaded_file()
    {
        $file = UploadedFile::fake()->image('avatar.jpg');
        $image = new PendingImage($file, new Filesystem);

        $this->assertSame($file, $image->file());
    }

    public function test_call_proxies_to_uploaded_file_without_options()
    {
        $file = UploadedFile::fake()->image('avatar.jpg');
        $image = new PendingImage($file, new Filesystem);

        $this->assertSame($file->getClientOriginalName(), $image->getClientOriginalName());
    }

    public function test_call_does_not_modify_file_without_options()
    {
        $file = UploadedFile::fake()->image('avatar.jpg', 200, 200);
        $originalBytes = file_get_contents($file->getRealPath());
        $image = new PendingImage($file, new Filesystem);

        $image->getClientOriginalName();

        $this->assertSame($originalBytes, file_get_contents($file->getRealPath()));
    }

    protected function makePendingImage(): PendingImage
    {
        return new PendingImage(
            UploadedFile::fake()->image('avatar.jpg'),
            new Filesystem,
        );
    }
}
