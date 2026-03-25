<?php

namespace Illuminate\Tests\Foundation\Image\Drivers;

use Illuminate\Foundation\Image\Drivers\GdDriver;
use Illuminate\Foundation\Image\ImageException;
use Illuminate\Foundation\Image\PendingImageOptions;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

#[RequiresPhpExtension('gd')]
class GdDriverTest extends TestCase
{
    public function test_processes_cover()
    {
        $driver = new GdDriver;
        $contents = $this->fakeImageContents(200, 200);

        $options = new PendingImageOptions;
        $options->coverWidth = 100;
        $options->coverHeight = 50;

        $result = $driver->process($contents, $options);

        [$width, $height] = getimagesizefromstring($result);

        $this->assertSame(100, $width);
        $this->assertSame(50, $height);
    }

    public function test_processes_optimize_to_webp()
    {
        $driver = new GdDriver;

        $options = new PendingImageOptions;
        $options->format = 'webp';

        $result = $driver->process($this->fakeImageContents(), $options);

        $this->assertSame(IMAGETYPE_WEBP, getimagesizefromstring($result)[2]);
    }

    public function test_processes_optimize_to_jpeg()
    {
        $driver = new GdDriver;

        $options = new PendingImageOptions;
        $options->format = 'jpg';

        $result = $driver->process($this->fakeImageContents(), $options);

        $this->assertSame(IMAGETYPE_JPEG, getimagesizefromstring($result)[2]);
    }

    public function test_processes_cover_and_optimize_together()
    {
        $driver = new GdDriver;
        $contents = $this->fakeImageContents(300, 300);

        $options = new PendingImageOptions;
        $options->coverWidth = 75;
        $options->coverHeight = 75;
        $options->format = 'webp';

        $result = $driver->process($contents, $options);

        [$width, $height, $type] = getimagesizefromstring($result);

        $this->assertSame(75, $width);
        $this->assertSame(75, $height);
        $this->assertSame(IMAGETYPE_WEBP, $type);
    }

    public function test_processes_scale()
    {
        $driver = new GdDriver;
        $contents = $this->fakeImageContents(400, 200);

        $options = new PendingImageOptions;
        $options->scaleWidth = 200;
        $options->scaleHeight = 200;

        $result = $driver->process($contents, $options);

        [$width, $height] = getimagesizefromstring($result);

        $this->assertSame(200, $width);
        $this->assertSame(100, $height);
    }

    public function test_scale_does_not_upscale()
    {
        $driver = new GdDriver;
        $contents = $this->fakeImageContents(100, 80);

        $options = new PendingImageOptions;
        $options->scaleWidth = 800;
        $options->scaleHeight = 600;

        $result = $driver->process($contents, $options);

        [$width, $height] = getimagesizefromstring($result);

        $this->assertSame(100, $width);
        $this->assertSame(80, $height);
    }

    public function test_format_conversion_preserves_dimensions()
    {
        $driver = new GdDriver;
        $contents = $this->fakeImageContents(300, 200);

        $options = new PendingImageOptions;
        $options->format = 'webp';

        $result = $driver->process($contents, $options);

        [$width, $height] = getimagesizefromstring($result);

        $this->assertSame(300, $width);
        $this->assertSame(200, $height);
    }

    public function test_quality_preserves_dimensions()
    {
        $driver = new GdDriver;
        $contents = $this->fakeImageContents(300, 200);

        $options = new PendingImageOptions;
        $options->quality = 50;

        $result = $driver->process($contents, $options);

        [$width, $height] = getimagesizefromstring($result);

        $this->assertSame(300, $width);
        $this->assertSame(200, $height);
    }

    public function test_processes_orient()
    {
        $driver = new GdDriver;
        $contents = $this->fakeImageContents(100, 100);

        $options = new PendingImageOptions;
        $options->orient = true;

        $result = $driver->process($contents, $options);

        [$width, $height] = getimagesizefromstring($result);

        $this->assertSame(100, $width);
        $this->assertSame(100, $height);
    }

    public function test_processes_blur()
    {
        $driver = new GdDriver;
        $contents = $this->fakeImageContents(100, 100);

        $options = new PendingImageOptions;
        $options->blur = 10;

        $result = $driver->process($contents, $options);

        $this->assertNotEmpty($result);
        $this->assertNotSame($contents, $result);
    }

    public function test_processes_greyscale()
    {
        $driver = new GdDriver;
        $contents = $this->fakeImageContents(100, 100);

        $options = new PendingImageOptions;
        $options->greyscale = true;

        $result = $driver->process($contents, $options);

        $this->assertNotEmpty($result);
        $this->assertNotSame($contents, $result);
    }

    public function test_processes_sharpen()
    {
        $driver = new GdDriver;
        $contents = $this->fakeImageContents(100, 100);

        $options = new PendingImageOptions;
        $options->sharpen = 10;

        $result = $driver->process($contents, $options);

        $this->assertNotEmpty($result);
        $this->assertNotSame($contents, $result);
    }

    public function test_processes_flip()
    {
        $driver = new GdDriver;
        $contents = $this->fakeImageContents(100, 100);

        $options = new PendingImageOptions;
        $options->flip = true;

        $result = $driver->process($contents, $options);

        $this->assertNotEmpty($result);
    }

    public function test_processes_flop()
    {
        $driver = new GdDriver;
        $contents = $this->fakeImageContents(100, 100);

        $options = new PendingImageOptions;
        $options->flop = true;

        $result = $driver->process($contents, $options);

        $this->assertNotEmpty($result);
    }

    public function test_throws_for_unsupported_input_format()
    {
        $driver = new GdDriver;

        $this->expectException(ImageException::class);
        $this->expectExceptionMessage('The image format [text/plain] is not supported.');

        $driver->process('not-an-image', new PendingImageOptions);
    }

    public function test_returns_image_without_options()
    {
        $driver = new GdDriver;
        $contents = $this->fakeImageContents(100, 100);

        $result = $driver->process($contents, new PendingImageOptions);

        [$width, $height] = getimagesizefromstring($result);

        $this->assertSame(100, $width);
        $this->assertSame(100, $height);
    }

    public function test_quality_affects_file_size()
    {
        $driver = new GdDriver;
        $contents = $this->fakeImageContents(100, 100);

        $lowQuality = new PendingImageOptions;
        $lowQuality->format = 'jpg';
        $lowQuality->quality = 1;

        $highQuality = new PendingImageOptions;
        $highQuality->format = 'jpg';
        $highQuality->quality = 100;

        $lowResult = $driver->process($contents, $lowQuality);
        $highResult = $driver->process($contents, $highQuality);

        $this->assertLessThan(strlen($highResult), strlen($lowResult));
    }

    public function test_ensure_requirements_passes()
    {
        $driver = new GdDriver;

        $driver->ensureRequirementsAreMet();

        $this->assertTrue(true);
    }

    protected function fakeImageContents(int $width = 100, int $height = 100): string
    {
        $file = UploadedFile::fake()->image('test.jpg', $width, $height);

        return file_get_contents($file->getRealPath());
    }
}
