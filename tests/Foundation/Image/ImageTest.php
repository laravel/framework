<?php

namespace Illuminate\Tests\Foundation\Image;

use Illuminate\Foundation\Image\Image;
use Illuminate\Foundation\Image\ImageException;
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
        $this->assertSame(75, $options->quality);
    }

    public function test_optimize_throws_for_unsupported_format()
    {
        $image = $this->makeImage();

        $this->expectException(ImageException::class);
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

    public function test_sharpen_returns_new_instance()
    {
        $image = $this->makeImage();

        $this->assertNotSame($image, $image->sharpen());
    }

    public function test_sharpen_sets_option()
    {
        $image = $this->makeImage();

        $this->assertSame(20, $this->getOptions($image->sharpen(20))->sharpen);
    }

    public function test_sharpen_has_default()
    {
        $image = $this->makeImage();

        $this->assertSame(10, $this->getOptions($image->sharpen())->sharpen);
    }

    public function test_flip_returns_new_instance()
    {
        $image = $this->makeImage();

        $this->assertNotSame($image, $image->flip());
    }

    public function test_flip_sets_option()
    {
        $image = $this->makeImage();

        $this->assertTrue($this->getOptions($image->flip())->flip);
    }

    public function test_flop_returns_new_instance()
    {
        $image = $this->makeImage();

        $this->assertNotSame($image, $image->flop());
    }

    public function test_flop_sets_option()
    {
        $image = $this->makeImage();

        $this->assertTrue($this->getOptions($image->flop())->flop);
    }

    public function test_width_returns_int()
    {
        $image = new Image($this->fakeImageContents(300, 200));

        $this->assertSame(300, $image->width());
    }

    public function test_height_returns_int()
    {
        $image = new Image($this->fakeImageContents(300, 200));

        $this->assertSame(200, $image->height());
    }

    public function test_to_base64_returns_encoded_string()
    {
        $contents = $this->fakeImageContents();
        $image = new Image($contents);

        $this->assertSame(base64_encode($contents), $image->toBase64());
    }

    public function test_to_data_uri_returns_data_uri()
    {
        $image = new Image($this->fakeImageContents());

        $dataUri = $image->toDataUri();

        $this->assertStringStartsWith('data:image/jpeg;base64,', $dataUri);
    }

    public function test_driver_exception_is_wrapped_in_image_exception()
    {
        $image = new Image($this->fakeImageContents());

        $this->expectException(ImageException::class);
        $this->expectExceptionMessage('Failed to process image:');

        // Trigger a driver error by using a non-existent driver
        $image->using('nonexistent')->cover(100, 100)->toBytes();
    }

    public function test_wrapped_exception_preserves_original()
    {
        $image = new Image($this->fakeImageContents());

        try {
            $image->using('nonexistent')->cover(100, 100)->toBytes();
        } catch (ImageException $e) {
            $this->assertNotNull($e->getPrevious());

            return;
        }

        $this->fail('ImageException was not thrown.');
    }

    public function test_to_bytes_returns_same_result_on_multiple_calls()
    {
        $image = new Image($this->fakeImageContents());

        $first = $image->toBytes();
        $second = $image->toBytes();

        $this->assertSame($first, $second);
    }

    public function test_to_bytes_without_operations_returns_original()
    {
        $contents = $this->fakeImageContents();
        $image = new Image($contents);

        $this->assertSame($contents, $image->toBytes());
    }

    public function test_has_changes_with_only_quality_set()
    {
        $image = $this->makeImage();
        $result = $image->quality(50);

        $this->assertTrue($this->getOptions($result)->hasChanges());
    }

    public function test_clone_does_not_share_hash_name_cache()
    {
        $image = $this->makeImage();
        $name1 = $image->hashName();

        $clone = $image->blur(1);
        $name2 = $image->hashName();

        // Same instance returns cached name
        $this->assertSame($name1, $name2);
    }

    public function test_hash_name_is_consistent_on_same_instance()
    {
        $image = $this->makeImage();

        $this->assertSame($image->hashName(), $image->hashName());
    }

    public function test_flip_and_flop_together()
    {
        $image = $this->makeImage();
        $result = $image->flip()->flop();

        $this->assertTrue($this->getOptions($result)->flip);
        $this->assertTrue($this->getOptions($result)->flop);
    }

    public function test_multiple_operations_chained()
    {
        $image = $this->makeImage();
        $result = $image->orient()->cover(200, 200)->blur(10)->greyscale()->sharpen(5)->toWebp()->quality(75);

        $options = $this->getOptions($result);

        $this->assertTrue($options->orient);
        $this->assertSame(200, $options->coverWidth);
        $this->assertSame(200, $options->coverHeight);
        $this->assertSame(10, $options->blur);
        $this->assertTrue($options->greyscale);
        $this->assertSame(5, $options->sharpen);
        $this->assertSame('webp', $options->format);
        $this->assertSame(75, $options->quality);
    }

    public function test_later_operation_overrides_earlier()
    {
        $image = $this->makeImage();
        $result = $image->cover(200, 200)->cover(100, 100);

        $options = $this->getOptions($result);

        $this->assertSame(100, $options->coverWidth);
        $this->assertSame(100, $options->coverHeight);
    }

    public function test_extension_returns_bin_for_unknown_mime()
    {
        $image = new Image('not-an-image');

        $this->assertSame('bin', $image->extension());
    }

    public function test_file_returns_null_for_non_upload()
    {
        $image = Image::class;
        $instance = new $image($this->fakeImageContents());

        $this->assertNull($instance->file());
    }

    public function test_using_gd_shortcut()
    {
        $image = $this->makeImage();
        $result = $image->usingGd();

        $driver = (new \ReflectionProperty($result, 'driver'))->getValue($result);

        $this->assertSame('gd', $driver);
    }

    public function test_using_imagick_shortcut()
    {
        $image = $this->makeImage();
        $result = $image->usingImagick();

        $driver = (new \ReflectionProperty($result, 'driver'))->getValue($result);

        $this->assertSame('imagick', $driver);
    }

    public function test_using_cloudflare_shortcut()
    {
        $image = $this->makeImage();
        $result = $image->usingCloudflare();

        $driver = (new \ReflectionProperty($result, 'driver'))->getValue($result);

        $this->assertSame('cloudflare', $driver);
    }

    public function test_dimensions_on_tiny_image()
    {
        $image = new Image($this->fakeImageContents(1, 1));

        $this->assertSame([1, 1], $image->dimensions());
        $this->assertSame(1, $image->width());
        $this->assertSame(1, $image->height());
    }

    public function test_to_data_uri_contains_valid_base64()
    {
        $image = new Image($this->fakeImageContents());

        $dataUri = $image->toDataUri();
        $base64Part = substr($dataUri, strpos($dataUri, ',') + 1);

        $this->assertNotFalse(base64_decode($base64Part, true));
    }

    public function test_optimize_throws_for_jpg_with_wrong_spelling()
    {
        $image = $this->makeImage();

        $this->expectException(ImageException::class);
        $this->expectExceptionMessage('The [png] format is not supported.');

        $image->optimize('png');
    }

    public function test_serialization_throws_exception()
    {
        $image = new Image($this->fakeImageContents());

        $this->expectException(ImageException::class);
        $this->expectExceptionMessage('Images cannot be serialized. Store the image first and serialize the path instead.');

        serialize($image);
    }

    public function test_pending_image_options_has_no_changes_by_default()
    {
        $options = new PendingImageOptions;

        $this->assertFalse($options->hasChanges());
    }

    public function test_pending_image_options_has_changes_with_zero_quality()
    {
        $options = new PendingImageOptions;
        $options->quality = 0;

        $this->assertTrue($options->hasChanges());
    }

    public function test_pending_image_options_has_changes_with_zero_blur()
    {
        $options = new PendingImageOptions;
        $options->blur = 0;

        $this->assertTrue($options->hasChanges());
    }

    public function test_pending_image_options_has_changes_with_zero_sharpen()
    {
        $options = new PendingImageOptions;
        $options->sharpen = 0;

        $this->assertTrue($options->hasChanges());
    }

    public function test_pending_image_options_default_quality_constant()
    {
        $this->assertSame(75, PendingImageOptions::DEFAULT_QUALITY);
    }

    public function test_cover_sets_both_dimensions()
    {
        $image = $this->makeImage();
        $result = $image->cover(300, 150);

        $options = $this->getOptions($result);

        $this->assertSame(300, $options->coverWidth);
        $this->assertSame(150, $options->coverHeight);
    }

    public function test_scale_sets_both_dimensions()
    {
        $image = $this->makeImage();
        $result = $image->scale(1200, 800);

        $options = $this->getOptions($result);

        $this->assertSame(1200, $options->scaleWidth);
        $this->assertSame(800, $options->scaleHeight);
    }

    public function test_orient_sets_option()
    {
        $image = $this->makeImage();
        $result = $image->orient();

        $this->assertTrue($this->getOptions($result)->orient);
    }

    public function test_optimize_sets_both_format_and_quality()
    {
        $image = $this->makeImage();
        $result = $image->optimize('jpg', 90);

        $options = $this->getOptions($result);

        $this->assertSame('jpg', $options->format);
        $this->assertSame(90, $options->quality);
    }

    public function test_optimize_throws_for_gif()
    {
        $this->expectException(ImageException::class);
        $this->expectExceptionMessage('The [gif] format is not supported.');

        $this->makeImage()->optimize('gif');
    }

    public function test_optimize_throws_for_avif()
    {
        $this->expectException(ImageException::class);
        $this->expectExceptionMessage('The [avif] format is not supported.');

        $this->makeImage()->optimize('avif');
    }

    public function test_optimize_allows_jpeg_spelling()
    {
        $image = $this->makeImage();
        $result = $image->optimize('jpeg', 90);

        $this->assertSame('jpeg', $this->getOptions($result)->format);
    }

    public function test_scale_does_not_set_cover()
    {
        $image = $this->makeImage();
        $result = $image->scale(800, 600);

        $options = $this->getOptions($result);

        $this->assertNull($options->coverWidth);
        $this->assertNull($options->coverHeight);
    }

    public function test_cover_does_not_set_scale()
    {
        $image = $this->makeImage();
        $result = $image->cover(200, 200);

        $options = $this->getOptions($result);

        $this->assertNull($options->scaleWidth);
        $this->assertNull($options->scaleHeight);
    }

    public function test_three_variants_from_same_source()
    {
        $image = $this->makeImage();

        $a = $image->cover(100, 100);
        $b = $image->scale(800, 600);
        $c = $image->blur(10);

        $this->assertSame(100, $this->getOptions($a)->coverWidth);
        $this->assertNull($this->getOptions($a)->scaleWidth);
        $this->assertNull($this->getOptions($a)->blur);

        $this->assertNull($this->getOptions($b)->coverWidth);
        $this->assertSame(800, $this->getOptions($b)->scaleWidth);
        $this->assertNull($this->getOptions($b)->blur);

        $this->assertNull($this->getOptions($c)->coverWidth);
        $this->assertNull($this->getOptions($c)->scaleWidth);
        $this->assertSame(10, $this->getOptions($c)->blur);
    }

    public function test_clone_resets_processed_flag()
    {
        $image = $this->makeImage();
        $clone = $image->cover(100, 100);

        $processed = (new \ReflectionProperty($clone, 'processed'))->getValue($clone);

        $this->assertFalse($processed);
    }

    public function test_using_sets_driver_string()
    {
        $image = $this->makeImage();
        $result = $image->using('custom-driver');

        $driver = (new \ReflectionProperty($result, 'driver'))->getValue($result);

        $this->assertSame('custom-driver', $driver);
    }

    public function test_implements_stringable()
    {
        $image = new Image($this->fakeImageContents());

        $this->assertInstanceOf(\Stringable::class, $image);
    }

    public function test_to_string_returns_data_uri()
    {
        $image = new Image($this->fakeImageContents());

        $this->assertSame($image->toDataUri(), $image->toString());
    }

    public function test_magic_to_string_returns_data_uri()
    {
        $image = new Image($this->fakeImageContents());

        $this->assertSame($image->toDataUri(), (string) $image);
    }

    public function test_image_exception_extends_runtime_exception()
    {
        $exception = new ImageException('test');

        $this->assertInstanceOf(\RuntimeException::class, $exception);
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
