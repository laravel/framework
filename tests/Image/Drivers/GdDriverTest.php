<?php

namespace Illuminate\Tests\Image\Drivers;

use Illuminate\Contracts\Image\Transformation;
use Illuminate\Http\UploadedFile;
use Illuminate\Image\Drivers\GdDriver;
use Illuminate\Image\ImageException;
use Illuminate\Image\ImagePipeline;
use Illuminate\Image\Transformations\Blur;
use Illuminate\Image\Transformations\Contain;
use Illuminate\Image\Transformations\Cover;
use Illuminate\Image\Transformations\Crop;
use Illuminate\Image\Transformations\FlipHorizontally;
use Illuminate\Image\Transformations\FlipVertically;
use Illuminate\Image\Transformations\Grayscale;
use Illuminate\Image\Transformations\Orient;
use Illuminate\Image\Transformations\Resize;
use Illuminate\Image\Transformations\Rotate;
use Illuminate\Image\Transformations\Scale;
use Illuminate\Image\Transformations\Sharpen;
use Intervention\Image\Interfaces\ImageInterface;
use PHPUnit\Framework\Attributes\RequiresFunction;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

#[RequiresPhpExtension('gd')]
class GdDriverTest extends TestCase
{
    public function test_processes_cover()
    {
        $driver = new GdDriver;
        $contents = $this->fakeImageContents(200, 200);

        $pipeline = $this->pipeline(new Cover(100, 50));

        $result = $driver->process($contents, $pipeline);

        [$width, $height] = getimagesizefromstring($result);

        $this->assertSame(100, $width);
        $this->assertSame(50, $height);
    }

    public function test_processes_optimize_to_webp()
    {
        $driver = new GdDriver;

        $pipeline = $this->pipeline(format: 'webp');

        $result = $driver->process($this->fakeImageContents(), $pipeline);

        $this->assertSame(IMAGETYPE_WEBP, getimagesizefromstring($result)[2]);
    }

    public function test_processes_optimize_to_jpeg()
    {
        $driver = new GdDriver;

        $pipeline = $this->pipeline(format: 'jpg');

        $result = $driver->process($this->fakeImageContents(), $pipeline);

        $this->assertSame(IMAGETYPE_JPEG, getimagesizefromstring($result)[2]);
    }

    public function test_processes_optimize_to_png()
    {
        $driver = new GdDriver;

        $pipeline = $this->pipeline(format: 'png');

        $result = $driver->process($this->fakeImageContents(), $pipeline);

        $this->assertSame(IMAGETYPE_PNG, getimagesizefromstring($result)[2]);
    }

    public function test_processes_optimize_to_gif()
    {
        $driver = new GdDriver;

        $pipeline = $this->pipeline(format: 'gif');

        $result = $driver->process($this->fakeImageContents(), $pipeline);

        $this->assertSame(IMAGETYPE_GIF, getimagesizefromstring($result)[2]);
    }

    #[RequiresFunction('imageavif')]
    public function test_processes_optimize_to_avif(): void
    {
        $driver = new GdDriver;

        $pipeline = $this->pipeline(format: 'avif');

        $result = $driver->process($this->fakeImageContents(), $pipeline);

        $this->assertSame(IMAGETYPE_AVIF, getimagesizefromstring($result)[2]);
    }

    #[RequiresFunction('imageavif')]
    public function test_processes_avif_input(): void
    {
        $driver = new GdDriver;
        $contents = $driver->process($this->fakeImageContents(), $this->pipeline(format: 'avif'));

        $result = $driver->process($contents, $this->pipeline(new Cover(50, 25), format: 'jpg'));

        $this->assertSame([50, 25], array_slice(getimagesizefromstring($result), 0, 2));
    }

    public function test_processes_optimize_to_bmp()
    {
        $driver = new GdDriver;

        $pipeline = $this->pipeline(format: 'bmp');

        $result = $driver->process($this->fakeImageContents(), $pipeline);

        $this->assertSame(IMAGETYPE_BMP, getimagesizefromstring($result)[2]);
    }

    public function test_processes_cover_and_optimize_together()
    {
        $driver = new GdDriver;
        $contents = $this->fakeImageContents(300, 300);

        $pipeline = $this->pipeline(new Cover(75, 75), format: 'webp');

        $result = $driver->process($contents, $pipeline);

        [$width, $height, $type] = getimagesizefromstring($result);

        $this->assertSame(75, $width);
        $this->assertSame(75, $height);
        $this->assertSame(IMAGETYPE_WEBP, $type);
    }

    public function test_processes_contain()
    {
        $driver = new GdDriver;
        $contents = $this->fakeImageContents(400, 200);

        $pipeline = $this->pipeline(new Contain(200, 200, '#ffffff'));

        $result = $driver->process($contents, $pipeline);

        [$width, $height] = getimagesizefromstring($result);

        $this->assertSame(200, $width);
        $this->assertSame(200, $height);
    }

    public function test_processes_contain_with_dominant_background()
    {
        $driver = new GdDriver;
        $contents = $this->solidColorImageContents(255, 0, 0, 400, 200);

        $pipeline = $this->pipeline(new Contain(200, 200, 'dominant'));

        $result = $driver->process($contents, $pipeline);

        [$width, $height] = getimagesizefromstring($result);

        $this->assertSame(200, $width);
        $this->assertSame(200, $height);
    }

    public function test_dominant_color_returns_hex_for_solid_image()
    {
        $driver = new GdDriver;
        $contents = $this->solidColorImageContents(0, 128, 255);

        $this->assertSame('#0080ff', $driver->dominantColor($contents));
    }

    public function test_dominant_color_ignores_alpha_channel(): void
    {
        $driver = new GdDriver;
        $contents = $this->semiTransparentColorImageContents(0, 128, 255, 128);

        $this->assertSame('#0080ff', $driver->dominantColor($contents));
    }

    public function test_processes_crop()
    {
        $driver = new GdDriver;
        $contents = $this->fakeImageContents(400, 200);

        $pipeline = $this->pipeline(new Crop(100, 50, 10, 20));

        $result = $driver->process($contents, $pipeline);

        [$width, $height] = getimagesizefromstring($result);

        $this->assertSame(100, $width);
        $this->assertSame(50, $height);
    }

    public function test_processes_resize()
    {
        $driver = new GdDriver;
        $contents = $this->fakeImageContents(400, 200);

        $pipeline = $this->pipeline(new Resize(200, 200));

        $result = $driver->process($contents, $pipeline);

        [$width, $height] = getimagesizefromstring($result);

        $this->assertSame(200, $width);
        $this->assertSame(200, $height);
    }

    public function test_processes_rotate()
    {
        $driver = new GdDriver;
        $contents = $this->fakeImageContents(100, 50);

        $pipeline = $this->pipeline(new Rotate(90));

        $result = $driver->process($contents, $pipeline);

        [$width, $height] = getimagesizefromstring($result);

        $this->assertSame(50, $width);
        $this->assertSame(100, $height);
    }

    public function test_processes_rotate_with_dominant_background()
    {
        $driver = new GdDriver;
        $contents = $this->solidColorImageContents(0, 255, 0, 100, 50);

        $pipeline = $this->pipeline(new Rotate(45, 'dominant'));

        $result = $driver->process($contents, $pipeline);

        $this->assertNotEmpty($result);
        $this->assertNotFalse(getimagesizefromstring($result));
    }

    public function test_processes_scale()
    {
        $driver = new GdDriver;
        $contents = $this->fakeImageContents(400, 200);

        $pipeline = $this->pipeline(new Scale(200, 200));

        $result = $driver->process($contents, $pipeline);

        [$width, $height] = getimagesizefromstring($result);

        $this->assertSame(200, $width);
        $this->assertSame(100, $height);
    }

    public function test_processes_scale_width_only()
    {
        $driver = new GdDriver;
        $contents = $this->fakeImageContents(400, 200);

        $pipeline = $this->pipeline(new Scale(200, null));

        $result = $driver->process($contents, $pipeline);

        [$width, $height] = getimagesizefromstring($result);

        $this->assertSame(200, $width);
        $this->assertSame(100, $height);
    }

    public function test_processes_scale_height_only()
    {
        $driver = new GdDriver;
        $contents = $this->fakeImageContents(400, 200);

        $pipeline = $this->pipeline(new Scale(null, 100));

        $result = $driver->process($contents, $pipeline);

        [$width, $height] = getimagesizefromstring($result);

        $this->assertSame(200, $width);
        $this->assertSame(100, $height);
    }

    public function test_scale_does_not_upscale()
    {
        $driver = new GdDriver;
        $contents = $this->fakeImageContents(100, 80);

        $pipeline = $this->pipeline(new Scale(800, 600));

        $result = $driver->process($contents, $pipeline);

        [$width, $height] = getimagesizefromstring($result);

        $this->assertSame(100, $width);
        $this->assertSame(80, $height);
    }

    public function test_format_conversion_preserves_dimensions()
    {
        $driver = new GdDriver;
        $contents = $this->fakeImageContents(300, 200);

        $pipeline = $this->pipeline(format: 'webp');

        $result = $driver->process($contents, $pipeline);

        [$width, $height] = getimagesizefromstring($result);

        $this->assertSame(300, $width);
        $this->assertSame(200, $height);
    }

    public function test_quality_preserves_dimensions()
    {
        $driver = new GdDriver;
        $contents = $this->fakeImageContents(300, 200);

        $pipeline = $this->pipeline(quality: 50);

        $result = $driver->process($contents, $pipeline);

        [$width, $height] = getimagesizefromstring($result);

        $this->assertSame(300, $width);
        $this->assertSame(200, $height);
    }

    public function test_processes_orient()
    {
        $driver = new GdDriver;
        $contents = $this->fakeImageContents(100, 100);

        $pipeline = $this->pipeline(new Orient);

        $result = $driver->process($contents, $pipeline);

        [$width, $height] = getimagesizefromstring($result);

        $this->assertSame(100, $width);
        $this->assertSame(100, $height);
    }

    public function test_processes_blur()
    {
        $driver = new GdDriver;
        $contents = $this->fakeImageContents(100, 100);

        $pipeline = $this->pipeline(new Blur(10));

        $result = $driver->process($contents, $pipeline);

        $this->assertNotEmpty($result);
        $this->assertNotSame($contents, $result);
    }

    public function test_processes_grayscale()
    {
        $driver = new GdDriver;
        $contents = $this->fakeImageContents(100, 100);

        $pipeline = $this->pipeline(new Grayscale);

        $result = $driver->process($contents, $pipeline);

        $this->assertNotEmpty($result);
        $this->assertNotSame($contents, $result);
    }

    public function test_processes_sharpen()
    {
        $driver = new GdDriver;
        $contents = $this->fakeImageContents(100, 100);

        $pipeline = $this->pipeline(new Sharpen(10));

        $result = $driver->process($contents, $pipeline);

        $this->assertNotEmpty($result);
        $this->assertNotSame($contents, $result);
    }

    public function test_processes_flip_vertically()
    {
        $driver = new GdDriver;
        $contents = $this->fakeImageContents(100, 100);

        $pipeline = $this->pipeline(new FlipVertically);

        $result = $driver->process($contents, $pipeline);

        $this->assertNotEmpty($result);
    }

    public function test_processes_flip_horizontally()
    {
        $driver = new GdDriver;
        $contents = $this->fakeImageContents(100, 100);

        $pipeline = $this->pipeline(new FlipHorizontally);

        $result = $driver->process($contents, $pipeline);

        $this->assertNotEmpty($result);
    }

    public function test_processes_custom_transformation()
    {
        $driver = new GdDriver;
        $transformation = new class implements Transformation {
            //
        };
        $received = null;

        $driver->transformUsing($transformation::class, function (ImageInterface $image, Transformation $transformation) use (&$received) {
            $received = $transformation;

            return $image->scaleDown(50, 50);
        });

        $result = $driver->process($this->fakeImageContents(100, 100), $this->pipeline($transformation));

        [$width, $height] = getimagesizefromstring($result);

        $this->assertSame($transformation, $received);
        $this->assertSame(50, $width);
        $this->assertSame(50, $height);
    }

    public function test_throws_for_unsupported_input_format()
    {
        $driver = new GdDriver;

        $this->expectException(ImageException::class);
        $this->expectExceptionMessageIs('The image format [text/plain] is not supported.');

        $driver->process('not-an-image', new ImagePipeline);
    }

    public function test_returns_image_without_options()
    {
        $driver = new GdDriver;
        $contents = $this->fakeImageContents(100, 100);

        $result = $driver->process($contents, new ImagePipeline);

        [$width, $height] = getimagesizefromstring($result);

        $this->assertSame(100, $width);
        $this->assertSame(100, $height);
    }

    public function test_quality_affects_file_size()
    {
        $driver = new GdDriver;
        $contents = $this->fakeImageContents(100, 100);

        $lowQuality = $this->pipeline(format: 'jpg', quality: 1);
        $highQuality = $this->pipeline(format: 'jpg', quality: 100);

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

    protected function solidColorImageContents(int $red, int $green, int $blue, int $width = 100, int $height = 100): string
    {
        $image = imagecreatetruecolor($width, $height);
        $color = imagecolorallocate($image, $red, $green, $blue);
        imagefill($image, 0, 0, $color);

        ob_start();
        imagepng($image);

        return ob_get_clean();
    }

    protected function semiTransparentColorImageContents(int $red, int $green, int $blue, int $alpha, int $width = 100, int $height = 100): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagesavealpha($image, true);
        // GD alpha runs 0 (opaque) to 127 (fully transparent), the inverse of a 0-255 alpha channel.
        $gdAlpha = (int) round((255 - $alpha) / 255 * 127);
        $color = imagecolorallocatealpha($image, $red, $green, $blue, $gdAlpha);
        imagefill($image, 0, 0, $color);

        ob_start();
        imagepng($image);

        return ob_get_clean();
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
