<?php

namespace Illuminate\Tests\Integration\Foundation\Image;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Image\PendingImage;
use Illuminate\Http\UploadedFile;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

#[RequiresPhpExtension('gd')]
class PendingImageTest extends TestCase
{
    public function test_cover_resizes_image()
    {
        $file = UploadedFile::fake()->image('avatar.jpg', 200, 200);

        $image = new PendingImage($file, new Filesystem);
        $image->cover(100, 100)->process();

        [$width, $height] = getimagesize($file->getRealPath());

        $this->assertSame(100, $width);
        $this->assertSame(100, $height);
    }

    public function test_optimize_converts_format()
    {
        $file = UploadedFile::fake()->image('avatar.jpg', 100, 100);

        $image = new PendingImage($file, new Filesystem);
        $image->optimize('png')->process();

        $this->assertSame(IMAGETYPE_PNG, getimagesize($file->getRealPath())[2]);
    }

    public function test_cover_and_optimize_together()
    {
        $file = UploadedFile::fake()->image('avatar.jpg', 300, 300);

        $image = new PendingImage($file, new Filesystem);
        $image->cover(50, 50)->optimize('webp')->process();

        [$width, $height, $extension] = getimagesize($file->getRealPath());

        $this->assertSame(50, $width);
        $this->assertSame(50, $height);
        $this->assertSame(IMAGETYPE_WEBP, $extension);
    }

    public function test_scale_resizes_proportionally()
    {
        $file = UploadedFile::fake()->image('photo.jpg', 400, 200);

        $image = new PendingImage($file, new Filesystem);
        $image->scale(200, 200)->process();

        [$width, $height] = getimagesize($file->getRealPath());

        $this->assertSame(200, $width);
        $this->assertSame(100, $height); // aspect ratio preserved
    }

    public function test_orient_processes_without_error()
    {
        $file = UploadedFile::fake()->image('photo.jpg', 100, 100);

        $image = new PendingImage($file, new Filesystem);
        $image->orient()->process();

        [$width, $height] = getimagesize($file->getRealPath());

        $this->assertSame(100, $width);
        $this->assertSame(100, $height);
    }

    public function test_blur_modifies_image()
    {
        $file = UploadedFile::fake()->image('photo.jpg', 100, 100);
        $originalBytes = file_get_contents($file->getRealPath());

        $image = new PendingImage($file, new Filesystem);
        $image->blur(10)->process();

        $this->assertNotSame($originalBytes, file_get_contents($file->getRealPath()));
    }

    public function test_greyscale_modifies_image()
    {
        $file = UploadedFile::fake()->image('photo.jpg', 100, 100);
        $originalBytes = file_get_contents($file->getRealPath());

        $image = new PendingImage($file, new Filesystem);
        $image->greyscale()->process();

        $this->assertNotSame($originalBytes, file_get_contents($file->getRealPath()));
    }

    public function test_full_avatar_pipeline()
    {
        $file = UploadedFile::fake()->image('avatar.jpg', 400, 400);

        $image = new PendingImage($file, new Filesystem);
        $image->orient()->cover(200, 200)->optimize('webp')->process();

        [$width, $height, $type] = getimagesize($file->getRealPath());

        $this->assertSame(200, $width);
        $this->assertSame(200, $height);
        $this->assertSame(IMAGETYPE_WEBP, $type);
    }

    public function test_full_album_pipeline()
    {
        $file = UploadedFile::fake()->image('photo.jpg', 4000, 3000);

        $image = new PendingImage($file, new Filesystem);
        $image->orient()->scale(1200, 1200)->optimize('webp', 85)->process();

        [$width, $height, $type] = getimagesize($file->getRealPath());

        $this->assertSame(1200, $width);
        $this->assertSame(900, $height);
        $this->assertSame(IMAGETYPE_WEBP, $type);
    }

    public function test_file_size_changes_after_processing()
    {
        $file = UploadedFile::fake()->image('avatar.jpg', 200, 200);
        $originalSize = $file->getSize();

        $image = new PendingImage($file, new Filesystem);
        $image->cover(10, 10)->process();

        clearstatcache();

        $this->assertNotSame($originalSize, $file->getSize());
    }

    public function test_processing_only_happens_once()
    {
        $file = UploadedFile::fake()->image('avatar.jpg', 200, 200);

        $image = new PendingImage($file, new Filesystem);

        $image->cover(100, 100);
        $image->process();

        $bytesAfterFirst = file_get_contents($file->getRealPath());

        $image->process();

        $this->assertSame($bytesAfterFirst, file_get_contents($file->getRealPath()));
    }

    public function test_no_options_does_not_modify_file()
    {
        $file = UploadedFile::fake()->image('avatar.jpg', 200, 200);
        $originalBytes = file_get_contents($file->getRealPath());

        $image = new PendingImage($file, new Filesystem);
        $image->process();

        $this->assertSame($originalBytes, file_get_contents($file->getRealPath()));
    }
}
