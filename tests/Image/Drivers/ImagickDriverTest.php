<?php

namespace Illuminate\Tests\Image\Drivers;

use Illuminate\Http\UploadedFile;
use Illuminate\Image\Drivers\ImagickDriver;
use Illuminate\Image\ImagePipeline;
use Illuminate\Image\Transformation;
use Illuminate\Image\Transformations\Blur;
use Illuminate\Image\Transformations\Cover;
use Illuminate\Image\Transformations\FlipHorizontally;
use Illuminate\Image\Transformations\FlipVertically;
use Illuminate\Image\Transformations\Greyscale;
use Illuminate\Image\Transformations\Orient;
use Illuminate\Image\Transformations\Scale;
use Illuminate\Image\Transformations\Sharpen;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

#[RequiresPhpExtension('imagick')]
class ImagickDriverTest extends TestCase
{
    public function test_processes_cover()
    {
        $driver = new ImagickDriver;
        $contents = $this->fakeImageContents(200, 200);

        $pipeline = $this->pipeline(new Cover(100, 50));

        $result = $driver->process($contents, $pipeline);

        [$width, $height] = getimagesizefromstring($result);

        $this->assertSame(100, $width);
        $this->assertSame(50, $height);
    }

    public function test_processes_optimize_to_webp()
    {
        $driver = new ImagickDriver;

        $pipeline = $this->pipeline(format: 'webp');

        $result = $driver->process($this->fakeImageContents(), $pipeline);

        $this->assertSame(IMAGETYPE_WEBP, getimagesizefromstring($result)[2]);
    }

    public function test_processes_optimize_to_jpeg()
    {
        $driver = new ImagickDriver;

        $pipeline = $this->pipeline(format: 'jpg');

        $result = $driver->process($this->fakeImageContents(), $pipeline);

        $this->assertSame(IMAGETYPE_JPEG, getimagesizefromstring($result)[2]);
    }

    public function test_processes_cover_and_optimize_together()
    {
        $driver = new ImagickDriver;
        $contents = $this->fakeImageContents(300, 300);

        $pipeline = $this->pipeline(new Cover(75, 75), format: 'webp');

        $result = $driver->process($contents, $pipeline);

        [$width, $height, $type] = getimagesizefromstring($result);

        $this->assertSame(75, $width);
        $this->assertSame(75, $height);
        $this->assertSame(IMAGETYPE_WEBP, $type);
    }

    public function test_processes_scale()
    {
        $driver = new ImagickDriver;
        $contents = $this->fakeImageContents(400, 200);

        $pipeline = $this->pipeline(new Scale(200, 200));

        $result = $driver->process($contents, $pipeline);

        [$width, $height] = getimagesizefromstring($result);

        $this->assertSame(200, $width);
        $this->assertSame(100, $height);
    }

    public function test_scale_does_not_upscale()
    {
        $driver = new ImagickDriver;
        $contents = $this->fakeImageContents(100, 80);

        $pipeline = $this->pipeline(new Scale(800, 600));

        $result = $driver->process($contents, $pipeline);

        [$width, $height] = getimagesizefromstring($result);

        $this->assertSame(100, $width);
        $this->assertSame(80, $height);
    }

    public function test_format_conversion_preserves_dimensions()
    {
        $driver = new ImagickDriver;
        $contents = $this->fakeImageContents(300, 200);

        $pipeline = $this->pipeline(format: 'webp');

        $result = $driver->process($contents, $pipeline);

        [$width, $height] = getimagesizefromstring($result);

        $this->assertSame(300, $width);
        $this->assertSame(200, $height);
    }

    public function test_quality_preserves_dimensions()
    {
        $driver = new ImagickDriver;
        $contents = $this->fakeImageContents(300, 200);

        $pipeline = $this->pipeline(quality: 50);

        $result = $driver->process($contents, $pipeline);

        [$width, $height] = getimagesizefromstring($result);

        $this->assertSame(300, $width);
        $this->assertSame(200, $height);
    }

    public function test_processes_orient()
    {
        $driver = new ImagickDriver;
        $contents = $this->fakeImageContents(100, 100);

        $pipeline = $this->pipeline(new Orient);

        $result = $driver->process($contents, $pipeline);

        [$width, $height] = getimagesizefromstring($result);

        $this->assertSame(100, $width);
        $this->assertSame(100, $height);
    }

    public function test_processes_blur()
    {
        $driver = new ImagickDriver;
        $contents = $this->fakeImageContents(100, 100);

        $pipeline = $this->pipeline(new Blur(10));

        $result = $driver->process($contents, $pipeline);

        $this->assertNotEmpty($result);
        $this->assertNotSame($contents, $result);
    }

    public function test_processes_greyscale()
    {
        $driver = new ImagickDriver;
        $contents = $this->fakeImageContents(100, 100);

        $pipeline = $this->pipeline(new Greyscale);

        $result = $driver->process($contents, $pipeline);

        $this->assertNotEmpty($result);
        $this->assertNotSame($contents, $result);
    }

    public function test_processes_sharpen()
    {
        $driver = new ImagickDriver;
        $contents = $this->fakeImageContents(100, 100);

        $pipeline = $this->pipeline(new Sharpen(10));

        $result = $driver->process($contents, $pipeline);

        $this->assertNotEmpty($result);
        $this->assertNotSame($contents, $result);
    }

    public function test_processes_flip_vertically()
    {
        $driver = new ImagickDriver;
        $contents = $this->fakeImageContents(100, 100);

        $pipeline = $this->pipeline(new FlipVertically);

        $result = $driver->process($contents, $pipeline);

        $this->assertNotEmpty($result);
    }

    public function test_processes_flip_horizontally()
    {
        $driver = new ImagickDriver;
        $contents = $this->fakeImageContents(100, 100);

        $pipeline = $this->pipeline(new FlipHorizontally);

        $result = $driver->process($contents, $pipeline);

        $this->assertNotEmpty($result);
    }

    public function test_returns_image_without_options()
    {
        $driver = new ImagickDriver;
        $contents = $this->fakeImageContents(100, 100);

        $result = $driver->process($contents, new ImagePipeline);

        [$width, $height] = getimagesizefromstring($result);

        $this->assertSame(100, $width);
        $this->assertSame(100, $height);
    }

    public function test_quality_affects_file_size()
    {
        $driver = new ImagickDriver;
        $contents = $this->fakeImageContents(100, 100);

        $lowQuality = $this->pipeline(format: 'jpg', quality: 1);
        $highQuality = $this->pipeline(format: 'jpg', quality: 100);

        $lowResult = $driver->process($contents, $lowQuality);
        $highResult = $driver->process($contents, $highQuality);

        $this->assertLessThan(strlen($highResult), strlen($lowResult));
    }

    public function test_ensure_requirements_passes()
    {
        $driver = new ImagickDriver;

        $driver->ensureRequirementsAreMet();

        $this->assertTrue(true);
    }

    protected function fakeImageContents(int $width = 100, int $height = 100): string
    {
        $file = UploadedFile::fake()->image('test.jpg', $width, $height);

        return file_get_contents($file->getRealPath());
    }

    protected function pipeline(?Transformation $transformation = null, ?string $format = null, ?int $quality = null): ImagePipeline
    {
        $pipeline = new ImagePipeline;

        if ($transformation) {
            $pipeline->add($transformation);
        }

        $pipeline->output->format = $format;
        $pipeline->output->quality = $quality;

        return $pipeline;
    }
}
