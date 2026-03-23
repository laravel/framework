<?php

namespace Illuminate\Tests\Foundation\Image;

use Illuminate\Foundation\Image\Image;
use Illuminate\Foundation\Image\PendingImageOptions;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\TestCase;

class ImageTest extends TestCase
{
    public function test_cover_returns_new_instance()
    {
        $image = $this->makeImage();
        $result = $image->cover(100, 200);

        $this->assertNotSame($image, $result);
    }

    public function test_scale_returns_new_instance()
    {
        $image = $this->makeImage();
        $result = $image->scale(800, 600);

        $this->assertNotSame($image, $result);
    }

    public function test_orient_returns_new_instance()
    {
        $image = $this->makeImage();
        $result = $image->orient();

        $this->assertNotSame($image, $result);
    }

    public function test_blur_returns_new_instance()
    {
        $image = $this->makeImage();
        $result = $image->blur(10);

        $this->assertNotSame($image, $result);
    }

    public function test_greyscale_returns_new_instance()
    {
        $image = $this->makeImage();
        $result = $image->greyscale();

        $this->assertNotSame($image, $result);
    }

    public function test_optimize_returns_new_instance()
    {
        $image = $this->makeImage();
        $result = $image->optimize('webp');

        $this->assertNotSame($image, $result);
    }

    public function test_quality_returns_new_instance()
    {
        $image = $this->makeImage();
        $result = $image->quality(80);

        $this->assertNotSame($image, $result);
    }

    public function test_to_webp_returns_new_instance()
    {
        $image = $this->makeImage();

        $this->assertNotSame($image, $image->toWebp());
    }

    public function test_to_jpg_returns_new_instance()
    {
        $image = $this->makeImage();

        $this->assertNotSame($image, $image->toJpg());
    }

    public function test_using_returns_new_instance()
    {
        $image = $this->makeImage();
        $result = $image->using('imagick');

        $this->assertNotSame($image, $result);
    }

    public function test_original_is_not_mutated()
    {
        $image = $this->makeImage();
        $originalOptions = clone $this->getOptions($image);

        $image->cover(100, 100)->optimize('webp');

        $this->assertEquals($originalOptions, $this->getOptions($image));
    }

    public function test_chained_operations_accumulate()
    {
        $image = $this->makeImage();
        $result = $image->cover(100, 100)->optimize('webp', 90)->blur(5);

        $options = $this->getOptions($result);

        $this->assertSame(100, $options->coverWidth);
        $this->assertSame(100, $options->coverHeight);
        $this->assertSame('webp', $options->format);
        $this->assertSame(90, $options->quality);
        $this->assertSame(5, $options->blur);
    }

    public function test_variants_from_same_source_are_independent()
    {
        $image = $this->makeImage();

        $thumb = $image->cover(100, 100);
        $large = $image->scale(800, 600);

        $thumbOptions = $this->getOptions($thumb);
        $largeOptions = $this->getOptions($large);

        $this->assertSame(100, $thumbOptions->coverWidth);
        $this->assertNull($thumbOptions->scaleWidth);

        $this->assertNull($largeOptions->coverWidth);
        $this->assertSame(800, $largeOptions->scaleWidth);
    }

    public function test_to_bytes_returns_string()
    {
        $contents = $this->fakeImageContents();
        $image = new Image($contents);

        $this->assertSame($contents, $image->toBytes());
    }

    public function test_to_bytes_with_closure()
    {
        $contents = $this->fakeImageContents();
        $image = new Image(fn () => $contents);

        $this->assertSame($contents, $image->toBytes());
    }

    public function test_closure_is_not_called_until_to_bytes()
    {
        $called = false;

        $image = new Image(function () use (&$called) {
            $called = true;

            return $this->fakeImageContents();
        });

        $this->assertFalse($called);

        $image->toBytes();

        $this->assertTrue($called);
    }

    public function test_mime_type_detects_jpeg()
    {
        $image = new Image($this->fakeImageContents());

        $this->assertSame('image/jpeg', $image->mimeType());
    }

    public function test_extension_returns_jpg_for_jpeg()
    {
        $image = new Image($this->fakeImageContents());

        $this->assertSame('jpg', $image->extension());
    }

    public function test_dimensions_returns_width_and_height()
    {
        $image = new Image($this->fakeImageContents(300, 200));

        $this->assertSame([300, 200], $image->dimensions());
    }

    public function test_hash_name_returns_name_with_extension()
    {
        $image = new Image($this->fakeImageContents());

        $name = $image->hashName();

        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]{40}\.jpg$/', $name);
    }

    public function test_hash_name_with_path()
    {
        $image = new Image($this->fakeImageContents());

        $name = $image->hashName('avatars');

        $this->assertStringStartsWith('avatars/', $name);
        $this->assertMatchesRegularExpression('/^avatars\/[a-zA-Z0-9]{40}\.jpg$/', $name);
    }

    public function test_file_returns_uploaded_file_when_provided()
    {
        $file = UploadedFile::fake()->image('avatar.jpg');
        $image = new Image(fn () => $file->getContent(), $file);

        $this->assertSame($file, $image->file());
    }

    public function test_file_returns_null_when_not_provided()
    {
        $image = new Image($this->fakeImageContents());

        $this->assertNull($image->file());
    }

    public function test_clone_preserves_uploaded_file()
    {
        $file = UploadedFile::fake()->image('avatar.jpg');
        $image = new Image(fn () => $file->getContent(), $file);

        $cloned = $image->cover(100, 100);

        $this->assertSame($file, $cloned->file());
    }

    public function test_optimize_has_defaults()
    {
        $image = $this->makeImage();
        $result = $image->optimize();

        $options = $this->getOptions($result);

        $this->assertSame('webp', $options->format);
        $this->assertSame(80, $options->quality);
    }

    public function test_optimize_throws_for_unsupported_format()
    {
        $image = $this->makeImage();

        $this->expectException(\Illuminate\Foundation\Image\ImageException::class);
        $this->expectExceptionMessage('The [bmp] format is not supported.');

        $image->optimize('bmp');
    }

    public function test_quality_sets_option()
    {
        $image = $this->makeImage();
        $result = $image->quality(60);

        $this->assertSame(60, $this->getOptions($result)->quality);
    }

    public function test_to_webp_sets_format()
    {
        $image = $this->makeImage();

        $this->assertSame('webp', $this->getOptions($image->toWebp())->format);
    }

    public function test_to_jpg_sets_format()
    {
        $image = $this->makeImage();

        $this->assertSame('jpg', $this->getOptions($image->toJpg())->format);
    }

    public function test_to_jpeg_is_alias_for_to_jpg()
    {
        $image = $this->makeImage();

        $this->assertSame('jpg', $this->getOptions($image->toJpeg())->format);
    }

    public function test_quality_survives_format_conversion()
    {
        $image = $this->makeImage();

        $this->assertSame(50, $this->getOptions($image->quality(50)->toJpg())->quality);
        $this->assertSame(90, $this->getOptions($image->quality(90)->toWebp())->quality);
    }

    public function test_format_and_quality_can_be_set_separately()
    {
        $image = $this->makeImage();
        $result = $image->toWebp()->quality(60);

        $options = $this->getOptions($result);

        $this->assertSame('webp', $options->format);
        $this->assertSame(60, $options->quality);
    }

    public function test_blur_has_default()
    {
        $image = $this->makeImage();
        $result = $image->blur();

        $this->assertSame(5, $this->getOptions($result)->blur);
    }

    protected function makeImage(): Image
    {
        return new Image($this->fakeImageContents());
    }

    protected function fakeImageContents(int $width = 100, int $height = 100): string
    {
        $file = UploadedFile::fake()->image('test.jpg', $width, $height);

        return file_get_contents($file->getRealPath());
    }

    protected function getOptions(Image $image): PendingImageOptions
    {
        return (new \ReflectionProperty($image, 'options'))->getValue($image);
    }
}
